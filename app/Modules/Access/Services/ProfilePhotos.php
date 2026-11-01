<?php

namespace App\Modules\Access\Services;

use App\Modules\Access\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Das Profilfoto (users.avatar_path). Der Upload wird neu gerechnet: mittig quadratisch zugeschnitten, auf höchstens 512 Pixel verkleinert und als JPEG
 * gespeichert. Dabei fallen Metadaten (Ort, Kamera) und eingebettete Inhalte weg. Die Datei liegt privat und wird nur über die Route profile.photo
 * ausgeliefert, die die Sichtbarkeit prüft.
 *
 * Fehlt auf dem Server die PHP-Erweiterung GD, wird die geprüfte Originaldatei unverändert gespeichert (ohne Zuschnitt; Metadaten bleiben erhalten).
 * Der Zuschnitt auf ein Quadrat übernimmt dann die Darstellung (object-fit: cover).
 */
final class ProfilePhotos
{
    public const DISK = 'local';

    public const SIZE = 512;

    public const MIN_SIDE = 200;

    public function store(User $user, UploadedFile $file): void
    {
        $info = @getimagesize($file->getRealPath());

        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new DomainException('Bitte laden Sie ein JPEG-, PNG- oder WebP-Bild hoch.');
        }

        [$width, $height] = $info;

        if (min($width, $height) < self::MIN_SIDE) {
            throw new DomainException('Das Bild ist zu klein. Jede Seite braucht mindestens '.self::MIN_SIDE.' Pixel.');
        }

        if ($width * $height > 40_000_000) {
            throw new DomainException('Das Bild ist zu groß. Bitte verkleinern Sie es vorab.');
        }

        if (! function_exists('imagecreatetruecolor')) {
            $extension = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2]];
            $path = 'profile-photos/'.Str::ulid().'.'.$extension;
            Storage::disk(self::DISK)->putFileAs('profile-photos', $file, basename($path));

            $this->remove($user);
            $user->forceFill(['avatar_path' => $path])->save();

            return;
        }

        $source = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG => @imagecreatefrompng($file->getRealPath()),
            default => @imagecreatefromwebp($file->getRealPath()),
        };

        if ($source === false) {
            throw new DomainException('Das Bild konnte nicht gelesen werden.');
        }

        $side = min($width, $height);
        $canvas = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2), self::SIZE, self::SIZE, $side, $side);

        ob_start();
        imagejpeg($canvas, null, 85);
        $bytes = (string) ob_get_clean();

        $path = 'profile-photos/'.Str::ulid().'.jpg';
        Storage::disk(self::DISK)->put($path, $bytes);

        $this->remove($user);
        $user->forceFill(['avatar_path' => $path])->save();
    }

    public function remove(User $user): void
    {
        if (filled($user->avatar_path)) {
            Storage::disk(self::DISK)->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }
    }
}
