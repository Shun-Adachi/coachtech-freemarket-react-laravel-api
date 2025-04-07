<?php

use Illuminate\Support\Facades\Storage;

//一時画像ファイルの移動処理
if (!function_exists('moveTempImageToPermanentLocation')) {
    function moveTempImageToPermanentLocation($tempImagePath, $imageDirectoryPath)
    {
        if (!$tempImagePath || !Storage::disk('public')->exists($tempImagePath)) {
            return null;
        }

        $newPath = $imageDirectoryPath . basename($tempImagePath);
        Storage::disk('public')->move($tempImagePath, $newPath);

        return $newPath;
    }
}

//一時画像のアップロードとセッション管理を行う
if (!function_exists('handleTempImageUpload')) {
    function handleTempImageUpload($request, $validator)
    {
        // 有効なファイルがアップロードされた場合の処理
        if ($request->hasFile('image') && !$validator->errors()->has('image')) {
            // 既存の一時ファイルを削除
            if ($request->temp_image) {
                Storage::disk('public')->delete($request->temp_image);
            }

            // 新しいファイルを保存
            $path = $request->file('image')->store('temp', 'public');

            // セッションにパスを保存
            $request->session()->flash('temp_image', $path);
        }
        // 有効なファイルがアップロードされておらず、一時ファイルが存在する場合の処理
        elseif ($request->temp_image) {
            // セッションにパスを保存
            $request->session()->flash('temp_image', $request->temp_image);
        }
    }
}
