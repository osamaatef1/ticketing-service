<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\TicketNote;
use App\Models\TicketReply;
use App\Support\Enums\TicketStatus;
use Illuminate\Database\Seeder;

class SampleTicketsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            ['ticket_number'=>'TKT-2026-00001','subject'=>'Lost luggage','description'=>'Suitcase missing','first_name'=>'Ali','email'=>'ali@a.com','phone'=>'500111','platform'=>'web','category_id'=>1,'status'=>TicketStatus::New,'created_by'=>1],
            ['ticket_number'=>'TKT-2026-00002','subject'=>'Hotel cleanliness','description'=>'Room dirty','first_name'=>'Sara','email'=>'sara@b.com','platform'=>'manual','category_id'=>2,'status'=>TicketStatus::Assigned,'admin_id'=>7,'assigned_at'=>$now,'created_by'=>1],
            ['ticket_number'=>'TKT-2026-00003','subject'=>'Bus delay','description'=>'Bus 30min late','first_name'=>'Khaled','email'=>'khaled@c.com','platform'=>'whatsapp','category_id'=>5,'status'=>TicketStatus::Closed,'closed_at'=>$now,'created_by'=>1],
            ['ticket_number'=>'TKT-2026-00004','subject'=>'Food complaint','description'=>'Cold meal','first_name'=>'Ahmed','email'=>'ahmed@d.com','platform'=>'web','category_id'=>8,'status'=>TicketStatus::Resolved,'created_by'=>1],
            ['ticket_number'=>'TKT-2026-00005','subject'=>'Check-in delay','description'=>'Waited 2 hours','first_name'=>'Noor','email'=>'noor@e.com','platform'=>'manual','category_id'=>4,'status'=>TicketStatus::PendingCustomer,'created_by'=>1],
            ['ticket_number'=>'TKT-2026-00006','subject'=>'Question','description'=>'Random q','first_name'=>'Mohammed','last_name'=>'Al-Saud','email'=>'mo@f.com','platform'=>'manual','status'=>TicketStatus::New,'created_by'=>1],
            ['ticket_number'=>'TKT-2026-00007','subject'=>'AC broken','description'=>'AC broken','first_name'=>'Layla','email'=>'layla@g.com','platform'=>'web','category_id'=>2,'status'=>TicketStatus::Assigned,'admin_id'=>1,'assigned_at'=>$now,'created_by'=>1],
            ['ticket_number'=>'TKT-2026-00008','subject'=>'spam','description'=>'x','first_name'=>'Spammer','email'=>'sp@m.com','platform'=>'web','is_valid'=>false,'status'=>TicketStatus::New,'created_by'=>1],
        ];

        foreach ($rows as $r) {
            Ticket::create($r);
        }

        TicketReply::create(['ticket_id'=>4,'description'=>'We will refund','replied_by'=>'admin','admin_id'=>1]);
        TicketNote::create(['ticket_id'=>5,'note'=>'Calling hotel manager','admin_id'=>1]);
    }
}
