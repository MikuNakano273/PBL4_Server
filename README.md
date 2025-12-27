# PBL4_Server
Server for PBL4 - Simple Anti Virus Software

## Installation (Only for admin)
To install the server, follow these steps:

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/PBL4_Server.git
   ```
2. Navigate to the project directory:
   ```bash
   cd PBL4_Server
   ```
3. Install dependencies:
   ```bash
   npm install
   ```
4. Open two terminal windows, each run:
   ```bash
   cloudflared tunnel --url http://127.0.0.1:3000
   cloudflared tunnel --url http://127.0.0.1:8000
   ```
5. Open project directory, run in terminal:
   ```bash
   php -S localhost:8000
   ```
6. Open folder ws-server, run in terminal:
   ```bash
   node server.js
   ```
7. Run apache server in Xampp or any other server software.

8. Set up the worker:
  Press Window + R, type "taskschd.msc" and run.
  Create 3 new tasks, each one represent a worker in the worker folder in the project directory
  Each task, set the trigger to run at startup and set the action to run the worker script.
  - Actions -> New -> Program/script
  + Program/script: point to the php.exe in php folder (If you don't have php installed, download it from https://www.php.net/downloads.php, extract and set the path to php.exe)
  + Add arguments: Point to the .php file in the worker folder (e.g., worker_vt.php)
  + Start in: point to the worker folder in the project directory
  - Conditions: Turn off the condition "Start the task only if the computer is on AC power"
  - Settings: Turn off the choice "Stop the task if it runs longer than..."
  After finished setting up the tasks, run all the tasks and just leave it running in the background.

9. Open web browser and run:
   ```bash
   http://127.0.0.1:8000/index.php
   ```
 
10. Click on the "Server address push" in the menu, open the terminal that open cloudflare on port 8000, you will see:
    ```bash
    Your quick Tunnel has been created! Visit it at (it may take some time to be reachable):
    https://xxx.trycloudflare.com
    ```
  
    copy the https://xxx.trycloudflare.com, paste  the link and push so that client can connect to the server.

11. You are done! Now you can see all pending reports in the "Pending list" in the menu, worker will automatically process the reports and update the status, you will decide to accept or reject the hash.

12. You can also add a new hash by clicking on the "Add hash" button in the menu.

13. If you want to update the database, click "Database version manage" in the menu, click the "Submit database to github & sourceforge" button.

14. If you want to stop the server, just close all the terminal, turn off apache and end all tasks.
