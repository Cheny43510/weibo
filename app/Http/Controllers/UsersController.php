<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Mail;

class UsersController extends Controller
{

    public function __construct()
    {
        /**
         * 用户认证,必须先登录
         */
        $this->middleware('auth', [
            'except' => ['show', 'create', 'store','index','confirmEmail']
        ]);

        // 限流 一个小时内只能提交 10 次请求；
        $this->middleware('throttle:10,60', [
            'only' => ['store']
        ]);
    }
    /**
     * 注册
     */
    public function create(){
        return view('users.create');
    }

    /**
     *显示用户信息
     */
    public function show(User $user){
        return view('users.show',compact('user'));
    }

    /**
     *储存用户数据
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $this->sendEmailConfirmationTo($user);
        session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收。');
        return redirect('/');
    }

    /**
     * 发送验证邮件
     *
     * @param [type] $user
     * @return void
     */
    protected function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm';
        $data = compact('user');
        $from = 'summer@example.com';
        $name = 'Summer';
        $to = $user->email;
        $subject = "感谢注册 Weibo 应用！请确认你的邮箱。";

        Mail::send($view, $data, function ($message) use ($from, $name, $to, $subject) {
            $message->from($from, $name)->to($to)->subject($subject);
        });
    }

    /**
     * 激活成功
     *
     * @param [type] $token
     * @return void
     */
    public function confirmEmail($token)
    {
        $user = User::where('activation_token', $token)->firstOrFail();

        $user->activated = true;
        $user->activation_token = null;
        $user->save();

        Auth::login($user);
        session()->flash('success', '恭喜你，激活成功！');
        return redirect()->route('users.show', [$user]);
    }
    /**
     * 显示用户信息编辑页面
     *
     * @param User $user
     * @return void
     */
    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return view('users.edit', compact('user'));
    }

    /**
     * 执行用户信息逻辑
     *
     * @param User $user
     * @param Request $request
     * @return void
     */
    public function update(User $user, Request $request)
    {
        $this->authorize('update', $user);
        $this->validate($request, [
            'name' => 'required|max:50',
            'password' => 'nullable|confirmed|min:6'
        ]);

        $data = [];
        $data['name'] = $request->name;
        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }
        $user->update($data);

        session()->flash('success', '个人资料更新成功！');

        return redirect()->route('users.show', $user);
    }
    /**
     * Undocumented function
     *
     * @return void
     */
    public function index()
    {
        $users = User::paginate(6);
        return view('users.index', compact('users'));
    }

    /**
     * 删除用户
     *
     * @param User $user
     * @return void
     */
    public function destroy(User $user)
    {
        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success', '成功删除用户！');
        return back();
    }
}
