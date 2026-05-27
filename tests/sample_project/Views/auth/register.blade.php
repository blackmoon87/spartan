@extends('layouts.main_blade')

@section('content')
    <div style="max-width: 450px; margin: 3rem auto;">
        <div class="card animate-fade-in">
            <h2 style="text-align: center; margin-bottom: 0.5rem; font-size: 1.8rem; background: linear-gradient(135deg, #60a5fa, #a78bfa); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Create Account</h2>
            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem; font-size: 0.95rem;">Join as a new author to start sharing your thoughts.</p>

            <form method="POST" action="{{ url('/register') }}">
                @csrf

                @if (!empty($errors))
                    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; color: #f87171; font-size: 0.9rem;">
                        <ul style="margin: 0; padding-left: 1.2rem;">
                            @foreach ($errors as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Full Name</label>
                    <input type="text" name="name" required value="{{ $old['name'] ?? '' }}" placeholder="e.g. John Doe" style="width: 100%; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Email Address</label>
                    <input type="email" name="email" required value="{{ $old['email'] ?? '' }}" placeholder="e.g. john@example.com" style="width: 100%; box-sizing: border-box;">
                </div>

                <div style="margin-bottom: 2rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Password (Min. 6 chars)</label>
                    <input type="password" name="password" required placeholder="••••••••" style="width: 100%; box-sizing: border-box;">
                </div>

                <button type="submit" style="width: 100%; padding: 0.85rem; font-size: 1rem; font-weight: 600; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border: none; cursor: pointer; transition: all 0.2s;">
                    Sign Up
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-secondary);">
                Already have an account? <a href="{{ url('/login') }}" style="color: #60a5fa; text-decoration: none; font-weight: 500; transition: color 0.2s;">Log in here</a>
            </div>
        </div>
    </div>
@endsection
