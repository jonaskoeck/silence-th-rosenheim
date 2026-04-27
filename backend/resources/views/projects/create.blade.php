<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create project</title>
</head>
<body>
    <h1>Create project</h1>

    <p><a href="{{ route('projects.index') }}">List all projects</a></p>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('projects.store') }}">
        @csrf
        <p><label>Name <input type="text" name="name"></label></p>
        <p><label>OpenStack project id <input type="text" name="open_stack_project_id"></label></p>
        <p><label>App credential id <input type="text" name="app_credential_id"></label></p>
        <p><label>App credential secret <input type="text" name="app_credential_secret"></label></p>
        <p><button type="submit">Save</button></p>
    </form>
</body>
</html>
