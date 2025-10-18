<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminUserController extends Controller
{
    /**
     * ユーザー一覧
     */
    public function index()
    {
        $users = User::with('avatar') // アバター画像を eager load
            ->paginate(20);
    
        // 各ユーザーごとにアバターURLを生成
        $users->getCollection()->transform(function ($user) {

        // ✅ media_files.type = 'avatar' のうち最初のものを取得
        $avatar = $user->mediaFiles()
            ->where('media_files.type', 'avatar')
            ->orderBy('media_relations.sort_order', 'asc')
            ->first();

        if ($avatar && $avatar->path) {
            $disk = $avatar->disk ?? 'public';
            $user->avatar_url = Storage::disk($disk)->url($avatar->path);
        } else {
            $user->avatar_url = null;
        }

            return $user;
        });
        
        return view('admin.users.index', compact('users'));
    }

    /**
     * ユーザー編集フォーム
     */
    public function edit(User $user)
    {
        // ✅ media_files.type = 'avatar' を基準に取得
        $avatar = $user->mediaFiles()
            ->where('media_files.type', 'avatar')
            ->orderBy('media_relations.sort_order', 'asc')
            ->first();
    
        // ✅ StorageからURLを生成（存在しない場合はnull）
        if ($avatar && $avatar->path) {
            $disk = $avatar->disk ?? 'public';
            $avatar_url = Storage::disk($disk)->url($avatar->path);
        } else {
            $avatar_url = null;
        }
        return view('admin.users.edit', compact('user', 'avatar_url'));
    }

    public function update(ProfileUpdateRequest $request, User $user)
    {
        // バリデーション済みデータを取得
        $data = $request->validated();
    
        $user->fill($data);
        $user->save();
    
        return redirect()->route('admin.users.index')->with('success', 'ユーザーを更新しました');
    }



    /**
     * ユーザー削除処理
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'ユーザーを削除しました。');
    }
}
