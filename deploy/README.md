# Deploying to the Synology NAS

Pushing to `main` builds an ARM64 image and publishes it to
`ghcr.io/no1done/forecaster` (see `.github/workflows/docker.yml`).
The NAS pulls that image and runs it next to Postgres.

## One-time setup

1. **DSM Package Center:** install **Container Manager** and **Tailscale**.
   Open Tailscale and sign in. Install Tailscale on each device you'll use
   and sign in with the same account.
2. **DSM Control Panel → Terminal & SNMP:** enable SSH.
3. **GitHub → Settings → Developer settings → Personal access tokens (classic):**
   create a token with only the `read:packages` scope.
4. SSH into the NAS and run:

   ```sh
   sudo -i
   mkdir -p /volume1/docker/forecaster && cd /volume1/docker/forecaster
   chown <dsm-user> /volume1/docker/forecaster   # so you can copy files in (step 5)
   docker login ghcr.io -u no1done   # paste the token as the password
   ```

5. Copy the three files from this `deploy/` folder onto the NAS. You don't
   need to clone the repo: the NAS runs the image built by GitHub Actions,
   so it only needs these files. From your computer, in the project root:

   ```sh
   scp -O deploy/compose.yaml deploy/.env.example deploy/backup.sh \
       <dsm-user>@<nas-ip>:/volume1/docker/forecaster/
   ```

   (`-O` uses the legacy scp protocol, which works even when SFTP is off in
   DSM. Uploading the files through File Station into
   `docker/forecaster` works too.)

   Back in the root shell on the NAS:

   ```sh
   cd /volume1/docker/forecaster
   cp .env.example .env
   openssl rand -hex 32   # run twice: once for APP_SECRET, once for POSTGRES_PASSWORD
   vi .env                # fill in the two secrets and DEFAULT_URI
   docker compose up -d
   docker compose exec app bin/console app:set-password you@example.com
   ```

6. Open `http://<nas-name>:8080` from any device on your tailnet.
   The NAS's name is in the Tailscale admin console; MagicDNS lets you
   use it directly.

## Updating

Push to `main`, wait for the **Build image** action to finish, then on the NAS:

```sh
cd /volume1/docker/forecaster && docker compose pull && docker compose up -d
```

Migrations run automatically when the app container starts.

## Backups

**DSM Control Panel → Task Scheduler → Create → Scheduled Task → User-defined script**,
as user `root`, daily:

```sh
sh /volume1/docker/forecaster/backup.sh
```

Dumps go to `/volume1/docker/forecaster/backups` and are kept for 30 days.
Point Hyper Backup at that folder to get copies off the NAS.

Restore into an empty database (this wipes the current data):

```sh
docker compose down -v && docker compose up -d --wait database
gunzip -c backups/forecaster-YYYY-MM-DD.sql.gz | docker compose exec -T database psql -U app app
docker compose up -d
```
