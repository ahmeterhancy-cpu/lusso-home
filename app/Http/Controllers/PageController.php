<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('pages.show', compact('page'));
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function sendContact(Request $request)
    {
        if ($request->filled('website')) { // bal küpü: botlar doldurur
            return back()->with('success', 'Mesajınız alındı.');
        }
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:40',
            'message' => 'required|string|max:3000',
            'store' => 'nullable|string|max:150',
        ], [], ['name' => 'Ad soyad', 'email' => 'E-posta', 'message' => 'Mesaj']);

        // Seçilen şube mesajın başına yazılır (yalnız sitedeki şube adları kabul edilir).
        $store = $data['store'] ?? null;
        unset($data['store']);
        if ($store && \App\Support\SiteContent::stores()->contains(fn ($s) => $s['label'].' – '.$s['place'] === $store)) {
            $data['message'] = '['.$store.'] '.$data['message'];
        }

        $msg = ContactMessage::create($data);
        try {
            if ($to = setting('email')) {
                Mail::raw("Ad: {$msg->name}\nE-posta: {$msg->email}\nTelefon: {$msg->phone}\n\n{$msg->message}", fn ($m) => $m->to($to)->replyTo($msg->email)->subject('İletişim formu: '.$msg->name));
            }
        } catch (\Throwable $e) {
            Log::error('İletişim e-postası gönderilemedi', ['e' => $e->getMessage()]);
        }

        return back()->with('success', 'Mesajınız için teşekkürler. En kısa sürede dönüş yapacağız.');
    }

    public function blog()
    {
        $posts = Post::where('is_active', true)->latest('published_at')->paginate(9);

        return view('pages.blog', compact('posts'));
    }

    public function post(string $slug)
    {
        $post = Post::where('slug', $slug)->where('is_active', true)->firstOrFail();

        return view('pages.post', compact('post'));
    }
}
