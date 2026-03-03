<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? '1000 A.D.' }}</title>
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: Verdana, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        a { color: aqua; text-decoration: none; }
        a:hover { color: red; text-decoration: underline overline; }
        .container { max-width: 800px; margin: 0 auto; }
        .title { font-size: 48px; font-weight: bold; text-align: center; margin-bottom: 10px; }
        .subtitle { text-align: center; font-size: 14px; margin-bottom: 20px; }
        .panel {
            border: 1px solid darkslategray;
            margin-bottom: 10px;
        }
        .panel-header {
            background-color: darkslategray;
            text-align: center;
            padding: 4px;
            font-weight: bold;
        }
        .panel-body {
            padding: 10px;
            background-image: url('/images/bg.gif');
        }
        .error { color: red; }
        .success { color: yellow; }
        input[type="text"], input[type="password"], input[type="email"] {
            padding: 4px;
            font-size: 12px;
        }
        input[type="submit"] {
            padding: 4px 16px;
            cursor: pointer;
        }
        table { border-collapse: collapse; }
        td { font-family: Verdana, sans-serif; font-size: 12px; }
        .flex-row { display: flex; gap: 10px; }
        .sidebar { width: 200px; flex-shrink: 0; }
        .main-content { flex: 1; }
    </style>
</head>
<body>
    <div class="container">
        <div class="title">1000 &nbsp; A. D.</div>
        <div class="subtitle">
            <b>1000 A.D. is a free turn based strategy game.<br>
            All you need to play is a web browser.</b>
        </div>

        <div class="flex-row">
            <div class="sidebar">
                @yield('sidebar')
            </div>
            <div class="main-content">
                @if(session('error'))
                    <p class="error">{{ session('error') }}</p>
                @endif
                @if(session('success'))
                    <p class="success">{{ session('success') }}</p>
                @endif
                @yield('content')
            </div>
        </div>

        <hr style="border: none; border-top: 1px solid darkslategray; margin-top: 20px;">
        <div style="text-align: center; font-size: 11px;">
            &copy; Copyright Ader Software 2000, 2001<br>
        </div>
    </div>
</body>
</html>
