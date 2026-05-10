<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Order Created
            [
                'code' => 'order_created',
                'channel' => 'email',
                'locale' => 'no',
                'subject' => 'Din bestilling {{order_number}} er mottatt',
                'body' => "Hei {{customer_name}},\n\nDin bestilling {{order_number}} på {{order_total}} er mottatt og behandles.\n\nEstimert levering: {{eta}}\n\nTakk for at du velger GLF BiKube!",
                'variables' => ['customer_name', 'order_number', 'order_total', 'eta'],
            ],
            [
                'code' => 'order_created',
                'channel' => 'email',
                'locale' => 'en',
                'subject' => 'Your order {{order_number}} has been received',
                'body' => "Hello {{customer_name}},\n\nYour order {{order_number}} for {{order_total}} has been received and is being processed.\n\nEstimated delivery: {{eta}}\n\nThank you for choosing GLF BiKube!",
                'variables' => ['customer_name', 'order_number', 'order_total', 'eta'],
            ],
            [
                'code' => 'order_created',
                'channel' => 'email',
                'locale' => 'ru',
                'subject' => 'Ваш заказ {{order_number}} получен',
                'body' => "Привет {{customer_name}},\n\nВаш заказ {{order_number}} на {{order_total}} получен и обрабатывается.\n\nОжидаемая доставка: {{eta}}\n\nСпасибо за выбор GLF BiKube!",
                'variables' => ['customer_name', 'order_number', 'order_total', 'eta'],
            ],

            // Payment Succeeded
            [
                'code' => 'payment_succeeded',
                'channel' => 'email',
                'locale' => 'no',
                'subject' => 'Betaling for {{order_number}} er gjennomført',
                'body' => "Hei {{customer_name}},\n\nBetalingen for din bestilling {{order_number}} på {{order_total}} er gjennomført.\n\nDin bestilling er nå bekreftet og vil bli behandlet.\n\nTakk!",
                'variables' => ['customer_name', 'order_number', 'order_total'],
            ],
            [
                'code' => 'payment_succeeded',
                'channel' => 'email',
                'locale' => 'en',
                'subject' => 'Payment for {{order_number}} completed',
                'body' => "Hello {{customer_name}},\n\nPayment for your order {{order_number}} of {{order_total}} has been completed.\n\nYour order is now confirmed and will be processed.\n\nThank you!",
                'variables' => ['customer_name', 'order_number', 'order_total'],
            ],

            // Courier Assigned
            [
                'code' => 'courier_assigned',
                'channel' => 'push',
                'locale' => 'no',
                'subject' => 'Kurér tildelt',
                'body' => "Hei {{courier_name}},\n\nDu har fått tildelt en ny oppgave:\n\nOppgave: {{task_description}}\nAdresse: {{delivery_address}}\nEstimert tid: {{eta}}\n\nVennligst bekreft mottak.",
                'variables' => ['courier_name', 'task_description', 'delivery_address', 'eta'],
            ],
            [
                'code' => 'courier_assigned',
                'channel' => 'push',
                'locale' => 'en',
                'subject' => 'Courier assigned',
                'body' => "Hello {{courier_name}},\n\nYou have been assigned a new task:\n\nTask: {{task_description}}\nAddress: {{delivery_address}}\nEstimated time: {{eta}}\n\nPlease confirm receipt.",
                'variables' => ['courier_name', 'task_description', 'delivery_address', 'eta'],
            ],

            // ETA Changed
            [
                'code' => 'eta_changed',
                'channel' => 'sms',
                'locale' => 'no',
                'subject' => null,
                'body' => 'GLF BiKube: Din bestilling {{order_number}} har ny estimert leveringstid: {{new_eta}}. Tidligere: {{old_eta}}.',
                'variables' => ['order_number', 'new_eta', 'old_eta'],
            ],
            [
                'code' => 'eta_changed',
                'channel' => 'sms',
                'locale' => 'en',
                'subject' => null,
                'body' => 'GLF BiKube: Your order {{order_number}} has new estimated delivery time: {{new_eta}}. Previous: {{old_eta}}.',
                'variables' => ['order_number', 'new_eta', 'old_eta'],
            ],

            // Order Completed
            [
                'code' => 'order_completed',
                'channel' => 'email',
                'locale' => 'no',
                'subject' => 'Din bestilling {{order_number}} er fullført',
                'body' => "Hei {{customer_name}},\n\nDin bestilling {{order_number}} er nå fullført og levert.\n\nTakk for at du brukte GLF BiKube! Vi håper du er fornøyd med tjenesten.\n\nVennlig hilsen,\nGLF BiKube Team",
                'variables' => ['customer_name', 'order_number'],
            ],
            [
                'code' => 'order_completed',
                'channel' => 'email',
                'locale' => 'en',
                'subject' => 'Your order {{order_number}} is completed',
                'body' => "Hello {{customer_name}},\n\nYour order {{order_number}} has been completed and delivered.\n\nThank you for using GLF BiKube! We hope you are satisfied with the service.\n\nBest regards,\nGLF BiKube Team",
                'variables' => ['customer_name', 'order_number'],
            ],

            // Refund Processed
            [
                'code' => 'refund_processed',
                'channel' => 'email',
                'locale' => 'no',
                'subject' => 'Refusjon for {{order_number}} er behandlet',
                'body' => "Hei {{customer_name}},\n\nRefusjonen for din bestilling {{order_number}} på {{refund_amount}} er behandlet og vil bli kreditert til din konto innen 3-5 virkedager.\n\nTakk for din forståelse.\n\nGLF BiKube Team",
                'variables' => ['customer_name', 'order_number', 'refund_amount'],
            ],
            [
                'code' => 'refund_processed',
                'channel' => 'email',
                'locale' => 'en',
                'subject' => 'Refund for {{order_number}} has been processed',
                'body' => "Hello {{customer_name}},\n\nThe refund for your order {{order_number}} of {{refund_amount}} has been processed and will be credited to your account within 3-5 business days.\n\nThank you for your understanding.\n\nGLF BiKube Team",
                'variables' => ['customer_name', 'order_number', 'refund_amount'],
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                [
                    'code' => $template['code'],
                    'channel' => $template['channel'],
                    'locale' => $template['locale'],
                ],
                array_merge($template, [
                    'is_active' => true,
                ])
            );
        }

        $this->command->info('Notification templates seeded successfully!');
    }
}
