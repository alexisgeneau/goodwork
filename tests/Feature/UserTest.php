<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->user = factory('App\Models\User')->create();
    }

    /** @test */
    public function admin_can_see_all_users()
    {
        factory('App\Models\User', 5)->create();
        $admin = factory('App\Models\User')->create([
            'role' => 2,
        ]);
        $users = User::all(['name', 'username', 'email', 'role', 'timezone', 'avatar']);
        $response = $this->actingAs($admin)->get('admin');
        $response->assertSeeInOrder($users->pluck('name', 'username', 'email', 'role', 'timezone', 'avatar')->toArray());
    }

    /** @test */
    public function user_can_upload_avatar_image()
    {
        $this->actingAs($this->user);
        Storage::fake('public');
        $this->post('users/' . $this->user->username . '/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.png'),
        ]);
        $this->assertEquals('storage/avatars/' . $this->user->username . '.png', auth()->user()->avatar);
        Storage::disk('public')->assertExists('avatars/' . $this->user->username . '.png');
    }

    /** @test */
    public function user_can_change_email_and_passwords()
    {
        $response = $this->actingAs($this->user)->put('users/' . $this->user->username . '/account', [
            'email'                         => 'new@email.com',
            'new_password'                  => 'new_password',
            'new_password_confirmation'     => 'new_password',
        ]);

        $response->assertJson([
            'status'  => 'success',
            'message' => 'Account details are updated',
        ]);
        $this->assertDatabaseHas('users', ['email' => 'new@email.com']);
        password_verify('new_password', $this->user->password);
    }
}
