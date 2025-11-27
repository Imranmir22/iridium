<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserImportService
{
    private array $stats = [];

    public function import($file)
    {
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        $this->stats['total'] = 0;
        $this->stats['failed'] = 0;
        $this->stats['completed'] = 0;

        while (($row = fgetcsv($handle)) != false) {
            $this->stats['total']++;
            $data = $this->buildData($row, $header);
            $validated = $this->validate($data);
            if ($validated) {
                $this->createRecord($data);
                $this->stats['completed']++;
            } else {
                $this->stats['failed']++;
            }
        }

        $this->logResults();
        return $this->stats['errors'];
    }

    private function buildData(array $row, array $header): array
    {
        return array_combine($header, $row);
    }

    private function validate(array $data): bool
    {
        $validator = Validator::make($data, [
            'user_id' => ['required', 'unique:users,user_id'],
            'username' => ['required', 'alpha_num', 'min:3', 'max:20'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:6', 'max:20']
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $this->stats['errors'][] = ['row'=>$data, 'errors'=>$errors];
            $errorMessage = "row with data =>" . implode('; ', $data) .  "";
            Log::warning("failed to import : " . $errorMessage);
            return false;
        }
        return true;
    }

    private function createRecord(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        DB::insert("INSERT into users (user_id, username, email,  password, created_at, updated_at) VALUES(?, ?, ?, ?, ?, ?)",[
            $data['user_id'],
            $data['username'],
            $data['email'],
            $data['password'],
            now(),
            now(),
        ]);
    }

    private function logResults()
    {
        $resultSummary = "Import completed - Total: " . $this->stats['total']
            . " Failed: " . $this->stats['failed']
            . " Completed: " . $this->stats['completed'];
        Log::info($resultSummary);

        if(!empty($this->stats['errors']))
        {
            foreach($this->stats['errors'] as $error)
            {
                Log::warning('Failed to created Record', $error);
            }
        }
    }
}
