@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 hover:text-blue-700">
                <x-icon name="list" class="h-4 w-4" />
                Back to tasks
            </a>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950">Create Task</h1>
            <p class="mt-2 text-sm text-slate-500">Add a task with priority, status, and due date.</p>
        </div>

        <form method="POST" action="{{ route('tasks.store') }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm space-y-5" autocomplete="off">
            @csrf
            @include('tasks._form')

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-5">
                <a href="{{ route('tasks.index') }}" class="inline-flex h-10 items-center justify-center rounded-md border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    Cancel
                </a>
                <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-blue-950 px-4 text-sm font-semibold text-white hover:bg-blue-900">
                    <x-icon name="check" class="h-4 w-4" />
                    Create Task
                </button>
            </div>
        </form>
    </div>
@endsection
