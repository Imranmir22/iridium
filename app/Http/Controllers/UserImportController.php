<?php

namespace App\Http\Controllers;

use App\Services\UserImportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class UserImportController extends Controller
{
    public function __construct(public UserImportService $userImportService)
    {
    }
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv'
        ]);
        try{
            DB::beginTransaction();
            $errorLogs = $this->userImportService->import($request->file);
            DB::commit();
            return response()->json(['message'=> 'imported successfully, below are records which couldn\'t complete', 'record'=> $errorLogs]);
        }catch(\Exception $e)
        {
            DB::rollBack();
            return response()->json(['message'=>'Failed to import users', 'error'=> $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
