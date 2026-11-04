# Docker secrets (not committed)

Create one file per secret, containing only the value:

- `db_password.txt`: PostgreSQL password for the `moodle` user.
- `admin_password.txt`: initial Moodle admin password (first install only; change it after first login).

Generate strong values, for example `openssl rand -base64 24 | tr -d "
" > db_password.txt`.
