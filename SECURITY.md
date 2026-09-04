# Security Policy

## Supported Versions

The project currently supports the latest `main` branch and the active development line on `develop`.

## Reporting a Vulnerability

Please do not disclose security vulnerabilities publicly in issues or pull requests.

Use one of the following channels:

- GitHub Security Advisories: open the repository's "Security" tab and choose "Report a vulnerability"
- If a private maintainer channel is configured in your organization, use that channel instead

Please include:

- a description of the vulnerability
- steps to reproduce it
- impact and affected area
- any suggested fix or mitigation

We aim to acknowledge valid reports promptly and coordinate a fix with the maintainers.

## Review and Remediation Expectations

- Security issues are handled with priority and may be fixed in a dedicated `security/*` branch
- Fixes should be reviewed by maintainers before merge
- Sensitive details should be minimized in public communication until the fix is available

## Operational Notes

- No production secrets should be committed to the repository
- Local `.env` files and generated credentials must remain out of source control
- Deployment secrets must be managed only through GitHub Environments or the hosting platform's secret store
