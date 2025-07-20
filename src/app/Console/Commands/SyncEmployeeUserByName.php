<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\User;

class SyncEmployeeUserByName extends Command
{
    protected $signature = 'employees:sync-user-by-name';
    protected $description = 'Sincroniza user_id en empleados por coincidencia de nombre';

    public function handle()
    {
        $count = 0;
        $employees = Employee::whereNull('user_id')->get();

        foreach ($employees as $employee) {
            $user = User::where('name', $employee->name)
                        ->whereIn('role', ['empleado', 'vendedor'])
                        ->first();

            if ($user) {
                $employee->user_id = $user->id;
                $employee->save();
                $this->info("✔ Vinculado: {$employee->name} → User ID {$user->id}");
                $count++;
            } else {
                $this->warn("✘ Sin coincidencia para: {$employee->name}");
            }
        }

        $this->info("🔁 Total vinculados: $count");
        return Command::SUCCESS;
    }
}
