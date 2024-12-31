<?php

use App\Http\Controllers\EmailQueueController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/push-emails', [EmailQueueController::class, 'pushEmailsToQueue']);
// Route::get('/process-queue', [EmailQueueController::class, 'processQueue']);
Route::get('/email-queue', [EmailQueueController::class, 'index'])->name('email-queue');
Route::post('/add-to-queue', [EmailQueueController::class, 'addToQueue'])->name('add-to-queue');
// Route::delete('/delete-message/{id}', [EmailQueueController::class, 'deleteMessage'])->name('delete-message');
// Route::post('/process-queue', [EmailQueueController::class, 'processQueue'])->name('process-queue');

Route::post('/send-message', [EmailQueueController::class, 'processQueue'])->name('send-message');
