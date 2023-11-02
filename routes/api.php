<?php

use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/register', [AuthController::class, 'signup']);
Route::post('/login', [AuthController::class, 'signin']);
Route::get('/logout', [AuthController::class, 'logout']);
Route::get('/UserPages/{id}', [AuthController::class,'UserPages']);
Route::get('/UserBranches/{id}', [AuthController::class,'UserBranches']);
// =================== PURCHASING =======================================
Route::get('/POReport/{id}', [AuthController::class,'POReport']);
Route::get('/PODetails/{po}', [AuthController::class,'PODetails']);
Route::get('/POItems/{po}', [AuthController::class,'POItems']);
Route::get('/POReceived/{po}', [AuthController::class,'POReceived']);
Route::get('/POInvoiced/{po}', [AuthController::class,'POInvoiced']);
Route::get('/POPaid/{po}', [AuthController::class,'POPaid']);
// =================== SALES ===============================================
Route::get('/SOReport/{id}', [AuthController::class,'SOReport']);
// ====================================================================
// Route::middleware('auth:sanctum')->group( function () {
//     Route::get('students', [StudentController::class, 'index']);
//     Route::post('students', [StudentController::class, 'store']);
//     Route::get('students/{id}', [StudentController::class, 'show']);
//     Route::get('students/{id}/edit', [StudentController::class, 'edit']);
//     Route::put('students/{id}/edit', [StudentController::class, 'update']);
//     Route::delete('students/{id}/delete', [StudentController::class, 'destroy']);
// });
// // ============================ test api ============================================
// Route::get('test/get', [StudentController::class, 'testGet']);
// Route::get('test/post', [StudentController::class, 'testPost']);
// Route::get('test/{id}/put', [StudentController::class, 'testPut']);