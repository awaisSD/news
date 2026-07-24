<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

/**
 * Creates the first admin user.
 *
 * Deliberately NOT called from DatabaseSeeder — a seeded admin account with
 * a hardcoded email/password is exactly the kind of default credential that
 * gets left in place on real deployments and found by anyone who knows the
 * codebase. Run this explicitly, once, when you actually need it:
 *
 *   php spark db:seed AdminUserSeeder
 *
 * For scripted/CI use you can supply values via environment variables
 * instead of the interactive prompts:
 *
 *   ADMIN_EMAIL=you@example.com ADMIN_NAME="Your Name" ADMIN_PASSWORD='...' \
 *     php spark db:seed AdminUserSeeder
 *
 * If ADMIN_PASSWORD isn't supplied, a strong random password is generated
 * and printed once at the end — copy it immediately. It is never written to
 * disk, logged, or stored anywhere except as the bcrypt hash in the
 * database, exactly like any other user's password.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = getenv('ADMIN_EMAIL') ?: CLI::prompt('Admin email', null, 'required|valid_email');
        $name  = getenv('ADMIN_NAME') ?: CLI::prompt('Admin name', 'Admin', 'required');

        $existing = $this->db->table('users')->where('email', $email)->get()->getRow();

        if ($existing !== null) {
            CLI::write("A user with email {$email} already exists (id {$existing->id}) — not creating a duplicate.", 'yellow');

            if ($existing->role !== 'admin') {
                CLI::write("That account's role is '{$existing->role}', not 'admin' — update it manually via SQL or /admin/users if you need admin access on it.", 'yellow');
            }

            return;
        }

        $password  = getenv('ADMIN_PASSWORD');
        $generated = false;

        if (! $password) {
            $password  = bin2hex(random_bytes(9)); // 18-char random hex string
            $generated = true;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'uuid'          => generate_uuid4(),
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => 'admin',
            'is_active'     => 1,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        CLI::write('Admin user created.', 'green');
        CLI::write("  Email: {$email}");

        if ($generated) {
            CLI::write("  Password: {$password}", 'yellow');
            CLI::write('  Copy this now — it is not stored anywhere and will not be shown again.', 'yellow');
        } else {
            CLI::write('  Password: (the value you supplied via ADMIN_PASSWORD)');
        }
    }
}
