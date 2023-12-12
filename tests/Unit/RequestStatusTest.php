<?php


use App\Models\RequestStatus;
use App\Models\Status;
use App\Models\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestStatusTest extends TestCase
{
    use RefreshDatabase;
    public function test_it_has_many_requests()
    {
        $status = RequestStatus::findOrFail(RequestStatus::OPEN);
        Request::factory(['description' => 'Request Description 1', 'status_id' => $status])->create();
        Request::factory(['description' => 'Request Description 2', 'status_id' => $status])->create();

        $i = 1;
        foreach ($status->requests as $request){
            $this->assertEquals('Request Description '.$i, $request->description);
            $i++;
        }
    }
}
