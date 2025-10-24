@extends('admin.layouts.app')

@section('panel')
<div class="container mt-4">
    <h2>Create SMS Template</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.template.storeSMS') }}">
        @csrf
        <div class="form-group">
            <label for="title">Title</label>
            <input type="hidden" class="form-control" name="type" value="sms">
            <input type="text" class="form-control" name="title" required placeholder="e.g. SMS Template 1" value="{{ old('title') }}">
            @error('title')
                <span class="text-danger d-block mt-1">{{ $message }}</span>
            @enderror
        </div>

        @csrf
        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" class="form-control" name="subject" required placeholder="e.g. Overdue collection related" value="{{ old('subject') }}">
            @error('subject')
                <span class="text-danger d-block mt-1">{{ $message }}</span>
            @enderror
        </div>

        {{-- ✅ Add Email Body with CKEditor --}}
        <div class="form-group mt-3">
            <label for="body">Body</label>
            <textarea name="body" id="body" rows="8" class="form-control" required>{{ old('body') }}</textarea>
            @error('body')
                <span class="text-danger d-block mt-1">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary mt-2">Add SMS Template</button>
        <a href="{{ route('admin.template.emailindex') }}" class="btn btn-secondary mt-2">Back</a>

        
    </form>
</div>

{{-- ✅ Put the script inline temporarily --}}
<script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (document.getElementById("body")) {
        CKEDITOR.replace("body", {
            height: 300,
            toolbar: [
                { name: "document", items: ["Source", "-", "Preview"] },
                { name: "clipboard", items: ["Cut", "Copy", "Paste", "-", "Undo", "Redo"] },
                { name: "basicstyles", items: ["Bold", "Italic", "Underline", "-", "RemoveFormat"] },
                { name: "paragraph", items: ["NumberedList", "BulletedList", "-", "JustifyLeft", "JustifyCenter", "JustifyRight"] },
                { name: "links", items: ["Link", "Unlink"] },
                { name: "insert", items: ["Image", "Table", "HorizontalRule"] },
                { name: "styles", items: ["Format", "Font", "FontSize"] },
                { name: "colors", items: ["TextColor", "BGColor"] }
            ]
        });
        console.log("✅ CKEditor initialized");
    } else {
        console.log("⚠️ No textarea found with id='body'");
    }
});
</script>
@endsection
