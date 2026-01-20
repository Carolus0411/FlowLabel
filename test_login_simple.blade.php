<!DOCTYPE html>
<html>
<head>
    <title>Test Login</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div style="padding: 50px; max-width: 400px; margin: 0 auto;">
        <h1>Test Login Form</h1>
        
        <form wire:submit="login">
            <div style="margin-bottom: 20px;">
                <label>Email</label>
                <input type="email" wire:model="email" style="width: 100%; padding: 10px; border: 1px solid #ccc;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label>Password</label>
                <input type="password" wire:model="password" style="width: 100%; padding: 10px; border: 1px solid #ccc;">
            </div>
            
            <button type="submit" style="width: 100%; padding: 10px; background: #5F489C; color: white; border: none; cursor: pointer;">
                Login
            </button>
        </form>
        
        <div style="margin-top: 20px;">
            <button onclick="console.log('Button clicked'); alert('Test');" style="padding: 10px; background: green; color: white;">
                Test Button
            </button>
        </div>
    </div>
    
    @livewireScripts
</body>
</html>
