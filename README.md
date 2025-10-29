## Overall Project Goal

The app does the following. Users will be able to upload CSV files into our system. Once uploaded, we will process the file in the background. We will then notify the user when the process completes. We will also show the user a history of all the file uploads. Here are the detailed specs.


> For quicker demo purpose, please copy the .env.example to .env. Warning: this .env is only for demo purpose and should not be used in production practice.

### Short Summary for Implementation
- Use Laravel Sail to setup the project with Docker
- Use Horizon to monitor the queues and background processing
- Use Redis as the queue and cache driver
- Use Maatwebsite Excel package to handle CSV import
- Use Soketi as the WebSocket server to broadcast events. Sail easily managed this service
- Use Laravel Echo and Pusher JS to listen to the events in the frontend
- Use Tailwind CSS for styling
- Use SQLite for database for simplicity
- Use Object Resource for API response formatting
- Implement clean UTF-8 handling for CSV import
- Uses unique file naming strategy to avoid file name collision to make sure the file upload is idempotent
- Implemented UPSERT logic when importing products to avoid duplicate entries by unique_key

### Lets Setup This Project Locally

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
- Upload the file and see the magic happen :). For now please upload one file at a time since there are few complication to be handled.

- Oh if needed, you can run `./vendor/bin/sail artisan test` to run the tests to make sure everything is working as expected.


## Example from Horizon Log for the upload process and status being handled
```
INFO  Horizon started successfully.  

2025-10-29 02:00:00 App\Jobs\ProcessProductImport .................. RUNNING
2025-10-29 02:00:00 App\Events\FileProcessingEvent ................. RUNNING
2025-10-29 02:00:00 App\Events\FileProcessingEvent ............ 73.73ms DONE
2025-10-29 02:00:02 App\Jobs\ProcessProductImport .................. 2s DONE
2025-10-29 02:00:02 Maatwebsite\Excel\Jobs\QueueImport ............. RUNNING
2025-10-29 02:00:02 Maatwebsite\Excel\Jobs\QueueImport ......... 5.28ms DONE
2025-10-29 02:00:02 App\Events\FileUploadedEvent ................... RUNNING
2025-10-29 02:00:02 App\Events\FileUploadedEvent .............. 26.47ms DONE
2025-10-29 02:00:02 Maatwebsite\Excel\Jobs\ReadChunk ............... RUNNING
2025-10-29 02:00:10 Maatwebsite\Excel\Jobs\ReadChunk ............... 8s DONE
2025-10-29 02:00:12 Maatwebsite\Excel\Jobs\ReadChunk ............... RUNNING
2025-10-29 02:00:20 Maatwebsite\Excel\Jobs\ReadChunk ............... 8s DONE
```

> Side note: Im writing this README.md by myself and not by AI. I understand all the process and Im happily to explain any part of the code if needed. Just reach me out!
