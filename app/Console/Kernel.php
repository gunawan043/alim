<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // AUTO-CLOSE LOWONGAN EXPIRED
        
        // Setiap jam cek dan tutup lowongan expired
        $schedule->command('recruitment:close-expired-jobs --days=0')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->emailOutputOnFailure(config('mail.admin_address'));
        
        // Setiap jam 23:55, tutup lowongan yang expired hari ini
        $schedule->command('recruitment:close-expired-jobs --days=0')
                 ->dailyAt('23:55')
                 ->withoutOverlapping()
                 ->runInBackground();
        
        // REMINDER LOWONGAN
        
        // Reminder H-7 setiap jam 9 pagi
        $schedule->command('recruitment:send-expiry-reminder --days=7')
                 ->dailyAt('09:00')
                 ->withoutOverlapping();
        
        // Reminder H-3 setiap jam 10 pagi
        $schedule->command('recruitment:send-expiry-reminder --days=3')
                 ->dailyAt('10:00')
                 ->withoutOverlapping();
        
        // Reminder H-1 setiap jam 11 pagi dan 4 sore
        $schedule->command('recruitment:send-expiry-reminder --days=1')
                 ->dailyAt('11:00')
                 ->withoutOverlapping();
        
        $schedule->command('recruitment:send-expiry-reminder --days=1')
                 ->dailyAt('16:00')
                 ->withoutOverlapping();
        
        // UPDATE KUOTA
        
        // Update kuota terisi setiap 6 jam
        $schedule->command('recruitment:update-filled-quotas')
                 ->everySixHours()
                 ->withoutOverlapping()
                 ->runInBackground();
        
        // REPORT GENERATION
        
        // Generate report harian setiap jam 1 pagi
        $schedule->command('recruitment:generate-report daily')
                 ->dailyAt('01:00')
                 ->withoutOverlapping();
        
        // Generate report mingguan setiap Senin jam 2 pagi
        $schedule->command('recruitment:generate-report weekly')
                 ->weeklyOn(1, '02:00')
                 ->withoutOverlapping();
        
        // Generate report bulanan setiap tanggal 1 jam 3 pagi
        $schedule->command('recruitment:generate-report monthly')
                 ->monthlyOn(1, '03:00')
                 ->withoutOverlapping();
        
        // CLEANUP

        // Hapus report lama > 3 bulan
        $schedule->command('recruitment:cleanup-reports --days=90')
                 ->weekly()
                 ->withoutOverlapping();

        // TODO REMINDER

        // Cek reminder todo setiap menit (harus dijalankan setiap menit untuk akurasi)
        $schedule->command('todo:send-reminders')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->emailOutputOnFailure(config('mail.admin_address'));

        // DORMITORY OVERDUE PERMITS

        // Cek permit overdue setiap jam (reminder + eskalasi)
        $schedule->command('dormitory:process-overdue')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
