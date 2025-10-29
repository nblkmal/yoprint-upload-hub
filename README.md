## Overall Project Goal

The app does the following. Users will be able to upload CSV files into our system. Once uploaded, we will process the file in the background. We will then notify the user when the process completes. We will also show the user a history of all the file uploads. Here are the detailed specs.

- check platform in compose.yaml

.env
- make sure APP_URL follow how it is in the docker compose file
- set CACHE_STORE and CACHE_DRIVER to redis

### Setup this project locally

- Run `./vendor/bin/sail up -d --build` to build the project using Docker
- You should see something like

```
✔ sail-8.4/app                                 Built                                                                         0.0s 
✔ Container yoprint-upload-hub-soketi-1        Started                                                                       0.1s 
✔ Container yoprint-upload-hub-redis-1         Started                                                                       0.2s 
✔ Container yoprint-upload-hub-laravel.test-1  Started                                                                       0.1s 
```
- Run `./vendor/bin/sail artisan migrate` to make sure the database is migrated
- Make sure to run `./vendor/bin/sail npm run dev` to compile the frontend assets.
- Do also run `./vendor/bin/sail artisan horizon` to make sure the queue is running. You can also visit http://localhost:80/horizon to see the horizon dashboard.
- Access the app in your browser at http://localhost:80/upload-file. As for now Im skipping the authentication for demo purpose.
- 
