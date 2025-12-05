<?php
use App\Models\User;
echo "--- START DEBUG ---\n";
try {
    $users = User::all();
    foreach($users as $u) {
        echo "User: " . $u->name . " (" . $u->email . ")\n";
        echo "Roles: " . implode(', ', $u->getRoleNames()->toArray()) . "\n";
        echo "----------------\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "--- END DEBUG ---\n";
