@extends('layouts.app')

@section('title', 'Become Worker')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-semibold mb-4">Become Worker</h1>
    <form method="POST" class="grid gap-3">
        @csrf
        <input name="name" placeholder="Name" class="border rounded px-3 py-2" required>
        <input name="email" placeholder="Email" class="border rounded px-3 py-2">
        <input name="phone" placeholder="Phone" class="border rounded px-3 py-2">
        <input name="city" placeholder="City" class="border rounded px-3 py-2">
        <input name="role_requested" placeholder="Role requested" class="border rounded px-3 py-2">
        <textarea name="experience" placeholder="Experience" class="border rounded px-3 py-2"></textarea>
        <button class="bg-slate-900 text-white rounded px-3 py-2">Submit</button>
    </form>
</div>
@endsection

