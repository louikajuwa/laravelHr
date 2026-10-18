<?php

namespace Tests\Feature\Attendance;

use App\Models\LateArrivalReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LateArrivalTest extends TestCase
{
    use RefreshDatabase;

    public function test_future_date_rejected(): void
    {
        $this->actingAs($this->employee())->post(route('me.late-arrivals.store'), [
            'date' => now()->addDay()->toDateString(), 'reason' => 'x',
        ])->assertSessionHasErrors('date');
        $this->assertSame(0, LateArrivalReport::count());
    }

    public function test_duplicate_rejected(): void
    {
        $emp = $this->employee();
        $data = ['date' => now()->subDay()->toDateString(), 'reason' => 'traffic'];

        $this->actingAs($emp)->post(route('me.late-arrivals.store'), $data)->assertSessionHasNoErrors();
        $this->actingAs($emp)->post(route('me.late-arrivals.store'), $data)->assertSessionHasErrors('date');
        $this->assertSame(1, LateArrivalReport::count());
    }

    public function test_review_only_when_pending(): void
    {
        $admin = $this->admin();
        $emp = $this->employee();
        $this->actingAs($emp)->post(route('me.late-arrivals.store'), ['date' => now()->toDateString(), 'reason' => 'x']);
        $report = LateArrivalReport::firstOrFail();

        $this->actingAs($admin)->post(route('hr.late-arrivals.review', $report), ['status' => 'excused'])->assertSessionHasNoErrors();
        $this->assertSame('excused', $report->fresh()->status);

        $this->actingAs($admin)->post(route('hr.late-arrivals.review', $report), ['status' => 'rejected'])->assertSessionHasErrors('report');
        $this->assertSame('excused', $report->fresh()->status);
    }
}
