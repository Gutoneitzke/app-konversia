<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Department;
use App\Models\User;
use App\Models\UserDepartment;
use App\Models\WhatsAppNumber;
use App\Models\WhatsAppSession;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WhatsAppNumbersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar empresa de exemplo
        $company = Company::firstOrCreate(
            ['slug' => 'konversia-demo'],
            [
                'name' => 'Konversia Demo',
                'active' => true,
                'settings' => [
                    'timezone' => 'America/Sao_Paulo',
                    'language' => 'pt_BR',
                ],
            ]
        );

        // Criar departamentos
        $departmentsData = [
            [
                'name' => 'Geral',
                'slug' => 'geral',
                'color' => '#3B82F6',
                'active' => true,
                'order' => 1,
            ],
            [
                'name' => 'Vendas',
                'slug' => 'vendas',
                'color' => '#10B981',
                'active' => true,
                'order' => 2,
            ],
            [
                'name' => 'Suporte',
                'slug' => 'suporte',
                'color' => '#F59E0B',
                'active' => true,
                'order' => 3,
            ],
        ];

        $departments = [];
        foreach ($departmentsData as $deptData) {
            $department = Department::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'slug' => $deptData['slug']
                ],
                $deptData
            );
            $departments[] = $department;
        }

        // Criar usuário admin
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Admin Demo',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
            ]
        );

        // Atribuir admin aos departamentos (apenas geral como primary)
        $generalDept = collect($departments)->firstWhere('slug', 'geral');
        if ($generalDept) {
            UserDepartment::firstOrCreate(
                [
                    'user_id' => $adminUser->id,
                    'department_id' => $generalDept->id,
                ],
                [
                    'is_primary' => true,
                    'active' => true,
                ]
            );
        }

        // Criar números WhatsApp de exemplo
        $whatsappNumbers = [
            [
                'phone_number' => '5511999999999',
                'nickname' => 'Principal',
                'description' => 'Número principal da empresa',
                'status' => 'active',
                'auto_reconnect' => true,
                'settings' => [
                    'welcome_message' => 'Olá! Como podemos ajudar?',
                    'business_hours' => [
                        'start' => '08:00',
                        'end' => '18:00',
                        'timezone' => 'America/Sao_Paulo',
                    ],
                ],
            ],
            [
                'phone_number' => '5511988888888',
                'nickname' => 'Vendas',
                'description' => 'Número dedicado para vendas',
                'status' => 'active',
                'auto_reconnect' => true,
                'settings' => [
                    'welcome_message' => 'Olá! Gostaria de falar com nosso time de vendas?',
                    'auto_assign_department' => 'vendas',
                ],
            ],
            [
                'phone_number' => '5511977777777',
                'nickname' => 'Suporte',
                'description' => 'Número para suporte técnico',
                'status' => 'inactive',
                'auto_reconnect' => true,
                'settings' => [
                    'welcome_message' => 'Olá! Precisa de ajuda técnica?',
                    'auto_assign_department' => 'suporte',
                ],
            ],
        ];

        $createdNumbers = [];
        foreach ($whatsappNumbers as $numberData) {
            $number = WhatsAppNumber::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'phone_number' => $numberData['phone_number']
                ],
                array_merge($numberData, [
                    'api_key' => \Illuminate\Support\Str::uuid()->toString(),
                ])
            );
            $createdNumbers[] = $number;
        }

        // Criar contatos de exemplo para o primeiro número ativo
        $activeNumber = collect($createdNumbers)->firstWhere('status', 'active');
        if ($activeNumber) {
            $contacts = [
                [
                    'jid' => '5511988887777@s.whatsapp.net',
                    'name' => 'João Silva',
                    'phone_number' => '5511988887777',
                    'is_business' => false,
                    'metadata' => [
                        'push_name' => 'João Silva',
                        'verified_name' => null,
                    ],
                ],
                [
                    'jid' => '5511977776666@s.whatsapp.net',
                    'name' => 'Maria Santos',
                    'phone_number' => '5511977776666',
                    'is_business' => true,
                    'metadata' => [
                        'push_name' => 'Maria Santos',
                        'verified_name' => 'Empresa XYZ',
                        'business_category' => 'Tecnologia',
                    ],
                ],
                [
                    'jid' => '5511966665555@s.whatsapp.net',
                    'name' => 'Pedro Oliveira',
                    'phone_number' => '5511966665555',
                    'is_business' => false,
                    'metadata' => [
                        'push_name' => 'Pedro Oliveira',
                        'status' => 'Disponível para conversar!',
                    ],
                ],
            ];

            foreach ($contacts as $contactData) {
                Contact::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'whatsapp_number_id' => $activeNumber->id,
                        'jid' => $contactData['jid']
                    ],
                    $contactData
                );
            }

            $this->command->info('Contatos de exemplo criados: ' . count($contacts));
        }

        $this->command->info('WhatsApp Numbers criados: ' . WhatsAppNumber::count());

        $this->command->info('Dados de demonstração criados com sucesso!');
        $this->command->info('Empresa: ' . $company->name);
        $this->command->info('Usuário admin: ' . $adminUser->email . ' (password: password)');
        $this->command->info('Números WhatsApp criados: ' . WhatsAppNumber::count());
        $this->command->info('Departamentos criados: ' . Department::count());
    }
}
