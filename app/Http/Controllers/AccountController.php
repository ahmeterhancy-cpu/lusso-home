<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::check()) {
            return view('account.auth', ['mode' => $request->query('action') === 'register' ? 'register' : 'login']);
        }

        return view('account.dashboard', ['user' => Auth::user(), 'recent' => Auth::user()->orders()->latest()->take(5)->get()]);
    }

    public function login(Request $request, WishlistService $wishlist)
    {
        $data = $request->validate(['email' => 'required|string', 'password' => 'required|string']);
        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'E-posta adresi veya şifre hatalı.']);
        }
        $cart = session('cart');
        $request->session()->regenerate();
        if ($cart) {
            session(['cart' => $cart]); // sepet girişten sonra da korunsun
        }
        $wishlist->mergeSessionIntoUser();

        return redirect()->intended(route('account'));
    }

    public function register(Request $request, WishlistService $wishlist)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
            'terms' => 'accepted',
        ], ['email.unique' => 'Bu e-posta adresiyle zaten bir hesap var.', 'terms.accepted' => 'Üyelik sözleşmesini onaylamalısınız.'],
            ['name' => 'Ad soyad', 'email' => 'E-posta', 'password' => 'Şifre']);

        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password'])]);
        $cart = session('cart');
        Auth::login($user);
        $request->session()->regenerate();
        if ($cart) {
            session(['cart' => $cart]);
        }
        $wishlist->mergeSessionIntoUser();

        return redirect()->route('account')->with('success', 'Hesabınız oluşturuldu. Hoş geldiniz!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function orders()
    {
        return view('account.orders', ['orders' => Auth::user()->orders()->latest()->paginate(10)]);
    }

    public function order(string $number)
    {
        $order = Auth::user()->orders()->where('number', $number)->with('items')->firstOrFail();

        return view('account.order', compact('order'));
    }

    public function edit()
    {
        return view('account.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:40',
            'address' => 'nullable|string|max:250',
            'city' => 'nullable|string|max:80',
            'current_password' => 'nullable|required_with:password|current_password',
            'password' => ['nullable', 'confirmed', PasswordRule::min(8)],
        ], ['current_password.current_password' => 'Mevcut şifre hatalı.']);

        $user->fill(collect($data)->only(['name', 'email', 'phone', 'address', 'city'])->all());
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return back()->with('success', 'Hesap bilgileriniz güncellendi.');
    }

    public function forgot()
    {
        return view('account.forgot');
    }

    public function sendReset(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        // Hesabın var olup olmadığını belli etmemek için her durumda aynı yanıt.
        return back()->with('success', 'Bu e-posta adresine kayıtlı bir hesap varsa şifre sıfırlama bağlantısı gönderildi.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('account.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', PasswordRule::min(8)]]);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('account')->with('success', 'Şifreniz değiştirildi, giriş yapabilirsiniz.')
            : back()->withErrors(['email' => 'Bağlantı geçersiz veya süresi dolmuş.']);
    }

    /** Misafir sipariş takibi: sipariş no + e-posta. */
    public function track(Request $request)
    {
        $order = null;
        if ($request->isMethod('post')) {
            $data = $request->validate(['number' => 'required|string', 'email' => 'required|email']);
            $order = Order::where('number', trim($data['number']))->where('email', $data['email'])->with('items')->first();
            if (! $order) {
                return back()->withInput()->with('error', 'Bu bilgilerle eşleşen bir sipariş bulunamadı.');
            }
        }

        return view('account.track', compact('order'));
    }
}
