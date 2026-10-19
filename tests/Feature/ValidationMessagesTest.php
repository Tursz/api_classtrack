<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_validation_messages_are_in_portuguese(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'email' => 'O campo e-mail é obrigatório.',
            'password' => 'O campo senha é obrigatório.',
        ]);
    }

    public function test_register_validation_messages_are_in_portuguese(): void
    {
        $response = $this->postJson('/api/register', [
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name' => 'O campo nome é obrigatório.',
            'email' => 'Informe um endereço de e-mail válido.',
            'password' => 'A senha deve ter pelo menos 8 caracteres.',
        ]);
    }

    public function test_verify_pin_validation_messages_are_in_portuguese(): void
    {
        $response = $this->postJson('/api/verify-pin', [
            'pin_code' => '12',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'email' => 'O campo e-mail é obrigatório.',
            'pin_code' => 'O código PIN deve conter exatamente 6 dígitos.',
        ]);
    }

    public function test_plan_validation_messages_are_in_portuguese(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/plan', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'title' => 'O título do plano é obrigatório.',
            'value' => 'O valor do plano é obrigatório.',
        ]);
    }

    public function test_learner_validation_messages_are_in_portuguese(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/learner', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name' => 'O campo nome é obrigatório.',
            'email' => 'Informe um endereço de e-mail válido.',
        ]);
    }

    public function test_isolated_class_validation_messages_are_in_portuguese(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/isolated-classes', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name' => 'O nome da aula avulsa é obrigatório.',
            'date' => 'A data da aula é obrigatória.',
        ]);
    }

    public function test_group_validation_messages_are_in_portuguese(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/group', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'title' => 'O título do grupo é obrigatório.',
            'description' => 'A descrição do grupo é obrigatória.',
        ]);
    }

    public function test_payment_validation_messages_are_in_portuguese(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/payment', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'learner_id' => 'O aluno é obrigatório.',
            'value' => 'O valor é obrigatório.',
            'payment_method' => 'A forma de pagamento é obrigatória.',
            'date' => 'A data é obrigatória.',
            'status' => 'O status do pagamento é obrigatório.',
        ]);
    }

    public function test_lesson_validation_messages_are_in_portuguese(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/lesson', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'learner_id' => 'O aluno é obrigatório.',
            'date' => 'A data da aula é obrigatória.',
            'start_at' => 'O horário de início é obrigatório.',
            'end_at' => 'O horário de término é obrigatório.',
        ]);
    }

    public function test_group_lesson_validation_messages_are_in_portuguese(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/group-lesson', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'group_id' => 'O grupo é obrigatório.',
            'date' => 'A data da aula é obrigatória.',
            'start_at' => 'O horário de início é obrigatório.',
            'end_at' => 'O horário de término é obrigatório.',
        ]);
    }
}
