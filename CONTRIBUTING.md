# Contributing

Thanks for contributing to the Vereinsportal project.

## Development Workflow

1. Create a feature or fix branch from `develop`
2. Keep changes focused and scoped
3. Add or update tests for behavior changes
4. Run the local validation checks before opening a pull request
5. Open a pull request against `develop` or a release branch as appropriate

## Branching

- `main`: production-ready and stable
- `develop`: active integration branch
- `feature/*`, `fix/*`, `security/*`: normal work streams
- `release/*` and `hotfix/*`: release and emergency workflows

## Quality Gates

Before opening or merging a change:

- `composer validate`
- `php artisan test`
- `php vendor/bin/pint --test`

For CI parity, the repository workflow also runs these checks automatically.

## Pull Request Expectations

- Keep the PR small and reviewable
- Explain the problem and the solution
- Mention any relevant issue or documentation link
- Include a brief note on security or data-protection impact if applicable

## Security and Data Protection

- Never commit secrets, personal data, or production environment details
- Do not expose confidential information in logs or test fixtures
- Report vulnerabilities privately via the security policy
