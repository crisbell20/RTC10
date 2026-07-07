# Auto-Close Exams Setup

This system automatically closes exams 30 minutes after their deadline passes.

## How It Works

1. **Automatic on Dashboard Load**: When students view their dashboard, the system checks and closes expired exams
2. **Scheduled Task (Optional)**: For better reliability, set up a cron job to run periodically

## Option 1: Automatic (Already Working)

The system automatically closes exams when:
- Students load their dashboard
- The deadline + 30 minutes has passed

No setup needed - this is already active!

## Option 2: Cron Job (Recommended for Production)

For more reliable auto-closing, set up a scheduled task:

### Windows (Task Scheduler)

1. Open Task Scheduler
2. Create Basic Task
3. Name: "Auto Close Exams"
4. Trigger: Daily, repeat every 5 minutes
5. Action: Start a program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\RTC10\scripts\auto-close-exams.php`
6. Save

### Linux/Mac (Crontab)

1. Open terminal
2. Edit crontab: `crontab -e`
3. Add this line:
   ```
   */5 * * * * php /path/to/RTC10/scripts/auto-close-exams.php >> /path/to/RTC10/logs/auto-close.log 2>&1
   ```
4. Save and exit

This runs every 5 minutes and logs output.

## Testing

Run manually to test:
```bash
php scripts/auto-close-exams.php
```

You should see output like:
```
[2026-04-06 16:30:00] Auto-closed 2 exam(s)
```

## How the 30-Minute Grace Period Works

Example:
- Exam deadline: April 6, 2026 4:00 PM
- Grace period ends: April 6, 2026 4:30 PM
- Status changes to "Closed" at 4:30 PM

Students can still START the exam until 4:00 PM. After 4:30 PM, the exam is automatically closed.
