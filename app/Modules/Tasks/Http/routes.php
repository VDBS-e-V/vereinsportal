<?php

use App\Modules\Tasks\Http\Controllers\MyTasksController;
use App\Modules\Tasks\Http\Controllers\ProjectController;
use App\Modules\Tasks\Http\Controllers\TaskController;
use App\Modules\Tasks\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

/*
 * Bereich Aufgaben: /aufgaben (Projekte), /aufgaben/meine, /aufgaben/projekte/{projekt} (Kanban und Liste), /aufgaben/{aufgabe}.
 * Berechtigung tasks.use; was jemand sieht, bestimmt die Mitgliedschaft im Projekt (Support: Services\ProjectAccess), Projekte anlegen und löschen erfordert tasks.admin.
 */
Route::middleware(['web', 'auth', 'can:tasks.use'])->prefix('aufgaben')->name('tasks.')->group(function (): void {
    Route::get('/', [ProjectController::class, 'index'])->name('index');
    Route::get('/meine', [MyTasksController::class, 'index'])->name('mine');

    Route::get('/projekte/neu', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projekte', [ProjectController::class, 'store'])->middleware('throttle:20,10')->name('projects.store');
    Route::get('/projekte/{project}', [ProjectController::class, 'show'])->whereNumber('project')->name('projects.show');
    Route::get('/projekte/{project}/einstellungen', [ProjectController::class, 'edit'])->whereNumber('project')->name('projects.edit');
    Route::put('/projekte/{project}', [ProjectController::class, 'update'])->whereNumber('project')->name('projects.update');
    Route::post('/projekte/{project}/archivieren', [ProjectController::class, 'archive'])->whereNumber('project')->name('projects.archive');
    Route::delete('/projekte/{project}', [ProjectController::class, 'destroy'])->whereNumber('project')->name('projects.destroy');
    Route::post('/projekte/{project}/aufgaben', [TaskController::class, 'store'])->whereNumber('project')->middleware('throttle:120,10')->name('store');
    Route::post('/projekte/{project}/aktion', [TaskController::class, 'bulk'])->whereNumber('project')->name('bulk');

    Route::get('/projekte/{project}/vorlagen', [TemplateController::class, 'index'])->whereNumber('project')->name('templates.index');
    Route::post('/projekte/{project}/vorlagen', [TemplateController::class, 'store'])->whereNumber('project')->middleware('throttle:60,10')->name('templates.store');
    Route::post('/projekte/{project}/vorlagen/loeschen', [TemplateController::class, 'destroyMany'])->whereNumber('project')->name('templates.destroy-many');
    Route::get('/projekte/{project}/vorlagen/{template}/bearbeiten', [TemplateController::class, 'edit'])->whereNumber(['project', 'template'])->name('templates.edit');
    Route::put('/projekte/{project}/vorlagen/{template}', [TemplateController::class, 'update'])->whereNumber(['project', 'template'])->name('templates.update');
    Route::post('/projekte/{project}/vorlagen/anwenden', [TemplateController::class, 'use'])->whereNumber('project')->middleware('throttle:120,10')->name('templates.use');

    Route::get('/{task}', [TaskController::class, 'show'])->whereNumber('task')->name('show');
    Route::put('/{task}', [TaskController::class, 'update'])->whereNumber('task')->name('update');
    Route::post('/{task}/verschieben', [TaskController::class, 'move'])->whereNumber('task')->middleware('throttle:240,10')->name('move');
    Route::delete('/{task}', [TaskController::class, 'destroy'])->whereNumber('task')->name('destroy');
    Route::post('/{task}/unteraufgaben', [TaskController::class, 'storeSubtask'])->whereNumber('task')->middleware('throttle:120,10')->name('subtasks.store');
    Route::post('/{task}/unteraufgaben/{subtask}/umschalten', [TaskController::class, 'toggleSubtask'])->whereNumber(['task', 'subtask'])->name('subtasks.toggle');
    Route::delete('/{task}/unteraufgaben/{subtask}', [TaskController::class, 'destroySubtask'])->whereNumber(['task', 'subtask'])->name('subtasks.destroy');
    Route::post('/{task}/anhaenge', [TaskController::class, 'storeAttachments'])->whereNumber('task')->middleware('throttle:30,10')->name('attachments.store');
    Route::post('/{task}/anhaenge/loeschen', [TaskController::class, 'destroyAttachments'])->whereNumber('task')->name('attachments.destroy');
    Route::get('/{task}/anhaenge/{attachment}', [TaskController::class, 'downloadAttachment'])->whereNumber(['task', 'attachment'])->name('attachments.download');
    Route::post('/{task}/als-vorlage', [TemplateController::class, 'fromTask'])->whereNumber('task')->name('templates.from-task');
    Route::post('/{task}/kommentare', [TaskController::class, 'storeComment'])->whereNumber('task')->middleware('throttle:60,10')->name('comments.store');
    Route::delete('/{task}/kommentare/{comment}', [TaskController::class, 'destroyComment'])->whereNumber(['task', 'comment'])->name('comments.destroy');
});
