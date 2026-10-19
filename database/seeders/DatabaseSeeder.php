<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Plan;
use App\Models\Learner;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\GroupLesson;
use App\Models\Payment;
use App\Models\Classroom;
use App\Models\IsolatedClass; // Adicionado o Model

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('pt_BR');

        // Slots de 1h dentro do horário comercial (06h às 20h, aula termina até 21h)
        $horarios = [];
        for ($h = 6; $h <= 20; $h++) {
            $horarios[] = sprintf('%02d:00', $h);
        }

        // Controla os horários já ocupados por professor+data, pra nunca gerar sobreposição
        $booked = [];

        $pickDate = function () use ($faker) {
            do {
                $date = $faker->dateTimeBetween('-30 days', '+30 days');
            } while ((int) $date->format('w') === 0); // pula domingos
            return $date->format('Y-m-d');
        };

        $pickSlot = function (int $userId, string $date) use (&$booked, $horarios) {
            $usados = $booked[$userId][$date] ?? [];
            $disponiveis = array_values(array_diff($horarios, $usados));

            // Se todos os slots do dia já estiverem ocupados (raro), usa outro dia
            if (empty($disponiveis)) {
                $disponiveis = $horarios;
            }

            $start = $disponiveis[array_rand($disponiveis)];
            $booked[$userId][$date][] = $start;

            $end = sprintf('%02d:00', (int) substr($start, 0, 2) + 1);
            return [$start, $end];
        };

        $planosTitulos = [
            'Plano Individual Mensal',
            'Plano Individual Trimestral',
            'Plano Dupla Semanal',
            'Avaliação Física + Treino Personalizado',
            'Plano Online Acompanhamento',
        ];

        $metodosPagamento = ['pix', 'cc', 'dc', 'cash'];

        $nomesAulasAvulsas = [
            'Aula Experimental — Musculação',
            'Consultoria Pontual de Postura',
            'Treino Avulso de Corrida',
            'Avaliação Bioimpedância Avulsa',
            'Sessão Única de Alongamento / Mobilidade',
        ];

        $criarUsuarioComDados = function (User $user, int $qtdPlanos, int $qtdAlunos, int $qtdGrupos, int $qtdAulasAvulsas) use ($faker, $planosTitulos, $metodosPagamento, $nomesAulasAvulsas, $pickDate, $pickSlot) {
            // Planos
            $plans = [];
            $titulosDisponiveis = $faker->randomElements($planosTitulos, min($qtdPlanos, count($planosTitulos)));
            foreach ($titulosDisponiveis as $titulo) {
                $plans[] = Plan::create([
                    'user_id' => $user->id,
                    'title' => $titulo,
                    'description' => $faker->sentence(10),
                    'value' => $faker->randomFloat(2, 120, 450),
                ]);
            }

            // Alunos
            $learners = [];
            for ($k = 0; $k < $qtdAlunos; $k++) {
                $learners[] = Learner::create([
                    'user_id' => $user->id,
                    'plan_id' => $faker->randomElement($plans)->id,
                    'name' => $faker->name(),
                    'email' => $faker->unique()->safeEmail(),
                    'contact' => $faker->cellphoneNumber(),
                    'observation' => $faker->optional(0.3)->sentence(8),
                ]);
            }

            // Aulas individuais + pagamentos por aluno
            foreach ($learners as $learner) {
                for ($l = 0; $l < 4; $l++) {
                    $date = $pickDate();
                    [$startAt, $endAt] = $pickSlot($user->id, $date);

                    Lesson::create([
                        'learner_id' => $learner->id,
                        'date' => $date,
                        'start_at' => $startAt,
                        'end_at' => $endAt,
                    ]);
                }

                for ($p = 0; $p < 2; $p++) {
                    Payment::create([
                        'learner_id' => $learner->id,
                        'date' => $faker->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
                        'value' => $faker->randomFloat(2, 100, 450),
                        'status' => $faker->randomElement(['pending', 'concluded']),
                        'method' => $faker->randomElement($metodosPagamento),
                    ]);
                }
            }

            // Grupos/turmas
            $nomesGrupos = [
                'Turma Manhã — Funcional',
                'Turma Noite — HIIT',
                'Turma Fim de Semana — Musculação',
                'Turma Iniciantes — Condicionamento',
            ];
            $gruposEscolhidos = $faker->randomElements($nomesGrupos, min($qtdGrupos, count($nomesGrupos)));

            foreach ($gruposEscolhidos as $nomeGrupo) {
                $group = Group::create([
                    'user_id' => $user->id,
                    'title' => $nomeGrupo,
                    'description' => $faker->sentence(10),
                ]);

                $qtdAlunosGrupo = min($faker->numberBetween(2, 4), count($learners));
                $randomLearners = $faker->randomElements($learners, $qtdAlunosGrupo);
                foreach ($randomLearners as $randomLearner) {
                    Classroom::create([
                        'group_id' => $group->id,
                        'learner_id' => $randomLearner->id,
                    ]);
                }

                for ($gl = 0; $gl < 4; $gl++) {
                    $date = $pickDate();
                    [$startAt, $endAt] = $pickSlot($user->id, $date);

                    GroupLesson::create([
                        'group_id' => $group->id,
                        'date' => $date,
                        'start_at' => $startAt,
                        'end_at' => $endAt,
                    ]);
                }
            }

            // Aulas Avulsas (Isolated Classes)
            for ($ia = 0; $ia < $qtdAulasAvulsas; $ia++) {
                IsolatedClass::create([
                    'user_id' => $user->id,
                    'name' => $faker->randomElement($nomesAulasAvulsas),
                    'date' => $pickDate(),
                    'observation' => $faker->optional(0.4)->sentence(8),
                    'value' => $faker->randomFloat(2, 50, 180),
                ]);
            }
        };

        // Usuário Admin principal (mantido para testes/login)
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'email_verified_at' => Carbon::now(),
        ]);
        $criarUsuarioComDados($admin, qtdPlanos: 3, qtdAlunos: 8, qtdGrupos: 3, qtdAulasAvulsas: 5);

        // 3 professores adicionais, com volume de dados menor
        for ($i = 0; $i < 3; $i++) {
            $user = User::create([
                'name' => $faker->name(),
                'email' => $faker->unique()->safeEmail(),
                'password' => Hash::make('password'),
            ]);
            $criarUsuarioComDados($user, qtdPlanos: 2, qtdAlunos: 5, qtdGrupos: 2, qtdAulasAvulsas: 3);
        }
    }
}