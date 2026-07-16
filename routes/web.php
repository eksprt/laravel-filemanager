<?php

use Illuminate\Support\Facades\Route;
use Eksprt\FileManager\Http\Controllers\FileManagerController;

Route::get('/filemanager/uploader.min.js', [FileManagerController::class, 'uploader'])->name('filemanager.uploader');

Route::post('/dropzone/upload', [FileManagerController::class, 'upload'])->name('filemanager.dropzone.upload');
Route::post('/dropzone/delete', [FileManagerController::class, 'delete'])->name('filemanager.dropzone.delete');
