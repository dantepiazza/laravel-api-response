<?php

namespace DantePiazza\LaravelApiResponse\Tests;

use DantePiazza\LaravelApiResponse\ApiResponse;
use Illuminate\Http\JsonResponse;

class ApiResponseTest extends TestCase
{
    private function fresh(): ApiResponse
    {
        return app(ApiResponse::class)->reset();
    }

    // -------------------------------------------------------------------------
    // JSend status values
    // -------------------------------------------------------------------------

    public function test_success_status_is_string(): void
    {
        $data = $this->fresh()->success('OK')->response()->getData(true);

        $this->assertSame('success', $data['status']);
    }

    public function test_fail_status_is_string(): void
    {
        $data = $this->fresh()->notFound()->response()->getData(true);

        $this->assertSame('fail', $data['status']);
    }

    public function test_error_status_is_string(): void
    {
        $data = $this->fresh()->serverError()->response()->getData(true);

        $this->assertSame('error', $data['status']);
    }

    // -------------------------------------------------------------------------
    // HTTP code key
    // -------------------------------------------------------------------------

    public function test_code_key_is_present(): void
    {
        $data = $this->fresh()->success('OK')->response()->getData(true);

        $this->assertArrayHasKey('code', $data);
        $this->assertSame(200, $data['code']);
    }

    public function test_http_status_code_matches_code_key(): void
    {
        $response = $this->fresh()->notFound()->response();

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(404, $response->getData(true)['code']);
    }

    // -------------------------------------------------------------------------
    // data always present on success / fail
    // -------------------------------------------------------------------------

    public function test_data_is_null_when_not_set_on_success(): void
    {
        $payload = $this->fresh()->success('OK')->response()->getData(true);

        $this->assertArrayHasKey('data', $payload);
        $this->assertNull($payload['data']);
    }

    public function test_data_is_null_when_not_set_on_fail(): void
    {
        $payload = $this->fresh()->notFound()->response()->getData(true);

        $this->assertArrayHasKey('data', $payload);
        $this->assertNull($payload['data']);
    }

    public function test_data_is_populated_when_provided(): void
    {
        $payload = $this->fresh()
            ->success('OK', ['foo' => 'bar'])
            ->response()
            ->getData(true);

        $this->assertSame(['foo' => 'bar'], $payload['data']);
    }

    // -------------------------------------------------------------------------
    // message rules
    // -------------------------------------------------------------------------

    public function test_message_is_optional_on_success(): void
    {
        $payload = $this->fresh()->success()->response()->getData(true);

        $this->assertArrayNotHasKey('message', $payload);
    }

    public function test_message_is_included_when_provided(): void
    {
        $payload = $this->fresh()->success('Users retrieved')->response()->getData(true);

        $this->assertSame('Users retrieved', $payload['message']);
    }

    public function test_message_is_required_on_error_and_falls_back(): void
    {
        // serverError always sets a message, but if someone calls set() with null
        // the builder must supply a fallback.
        $payload = $this->fresh()
            ->set(ApiResponse::STATUS_ERROR, 500)
            ->response()
            ->getData(true);

        $this->assertArrayHasKey('message', $payload);
        $this->assertNotEmpty($payload['message']);
    }

    public function test_message_setter_overrides_without_changing_status(): void
    {
        $response = $this->fresh()
            ->success('original')
            ->message('overridden')
            ->response();

        $this->assertSame('overridden', $response->getData(true)['message']);
        $this->assertSame(200, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // fail: validation errors go into data
    // -------------------------------------------------------------------------

    public function test_validation_errors_land_in_data(): void
    {
        $errors = ['email' => 'Required.', 'name' => 'Too short.'];

        $payload = $this->fresh()
            ->validationError('Validation failed', $errors)
            ->response()
            ->getData(true);

        $this->assertSame('fail', $payload['status']);
        $this->assertSame(422, $payload['code']);
        $this->assertSame($errors, $payload['data']);
        $this->assertArrayNotHasKey('errors', $payload);
    }

    // -------------------------------------------------------------------------
    // error: data is optional
    // -------------------------------------------------------------------------

    public function test_data_omitted_on_error_when_not_set(): void
    {
        $payload = $this->fresh()->serverError()->response()->getData(true);

        $this->assertArrayNotHasKey('data', $payload);
    }

    public function test_data_included_on_error_when_provided(): void
    {
        $payload = $this->fresh()
            ->serverError('Oops', ['trace' => 'line 42'])
            ->response()
            ->getData(true);

        $this->assertArrayHasKey('data', $payload);
        $this->assertSame('line 42', $payload['data']['trace']);
    }

    // -------------------------------------------------------------------------
    // 2xx shorthand HTTP codes
    // -------------------------------------------------------------------------

    public function test_created_uses_201(): void
    {
        $this->assertSame(201, $this->fresh()->created()->response()->getStatusCode());
    }

    public function test_accepted_uses_202(): void
    {
        $this->assertSame(202, $this->fresh()->accepted()->response()->getStatusCode());
    }

    public function test_no_content_uses_204(): void
    {
        $this->assertSame(204, $this->fresh()->noContent()->response()->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // 4xx / 5xx shorthand HTTP codes
    // -------------------------------------------------------------------------

    public function test_conflict_uses_409(): void
    {
        $this->assertSame(409, $this->fresh()->conflict()->response()->getStatusCode());
    }

    public function test_too_many_requests_uses_429(): void
    {
        $this->assertSame(429, $this->fresh()->tooManyRequests()->response()->getStatusCode());
    }

    public function test_service_unavailable_uses_503(): void
    {
        $this->assertSame(503, $this->fresh()->serviceUnavailable()->response()->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // data() merging
    // -------------------------------------------------------------------------

    public function test_data_merges_multiple_calls(): void
    {
        $payload = $this->fresh()
            ->success('OK')
            ->data(['a' => 1])
            ->data(['b' => 2])
            ->response()
            ->getData(true);

        $this->assertSame(1, $payload['data']['a']);
        $this->assertSame(2, $payload['data']['b']);
    }

    // -------------------------------------------------------------------------
    // records() — manual
    // -------------------------------------------------------------------------

    public function test_records_manual_pagination(): void
    {
        $items   = [['id' => 1], ['id' => 2]];
        $payload = $this->fresh()
            ->success('OK')
            ->records($items, total: 50, page: 2, pageSize: 10)
            ->response()
            ->getData(true);

        $pag = $payload['data']['pagination'];

        $this->assertCount(2, $payload['data']['results']);
        $this->assertSame(50,  $pag['total']);
        $this->assertSame(10,  $pag['per_page']);
        $this->assertSame(2,   $pag['current_page']);
        $this->assertSame(5,   $pag['last_page']);
        $this->assertSame(11,  $pag['from']);   // (2-1)*10 + 1
        $this->assertSame(20,  $pag['to']);     // min(2*10, 50)
        $this->assertArrayHasKey('links', $pag);
    }

    public function test_records_defaults_total_to_count_when_omitted(): void
    {
        $items   = [['id' => 1], ['id' => 2], ['id' => 3]];
        $payload = $this->fresh()
            ->success()
            ->records($items)
            ->response()
            ->getData(true);

        $this->assertSame(3, $payload['data']['pagination']['total']);
        $this->assertSame(1, $payload['data']['pagination']['last_page']);
    }

    // -------------------------------------------------------------------------
    // Reset & state isolation
    // -------------------------------------------------------------------------

    public function test_state_resets_after_response(): void
    {
        $api = $this->fresh();
        $api->success('First')->response();

        $second = $api->success('Second')->response()->getData(true);

        $this->assertSame('Second', $second['message']);
        $this->assertNull($second['data']);
    }

    // -------------------------------------------------------------------------
    // Helper function
    // -------------------------------------------------------------------------

    public function test_api_helper_returns_same_instance(): void
    {
        $this->assertSame(api(), api());
    }

    public function test_api_helper_builds_valid_response(): void
    {
        $payload = api()->success('Via helper', ['key' => 'value'])->response()->getData(true);

        $this->assertSame('success', $payload['status']);
        $this->assertSame('Via helper', $payload['message']);
        $this->assertSame('value', $payload['data']['key']);
    }

    // -------------------------------------------------------------------------
    // Macro support
    // -------------------------------------------------------------------------

    public function test_macro_can_be_added(): void
    {
        ApiResponse::macro('teapot', function () {
            return $this->set(ApiResponse::STATUS_FAIL, 418, "I'm a teapot");
        });

        $response = $this->fresh()->teapot()->response();

        $this->assertSame(418, $response->getStatusCode());
        $this->assertSame("I'm a teapot", $response->getData(true)['message']);
    }

    // -------------------------------------------------------------------------
    // Config key overrides
    // -------------------------------------------------------------------------

    public function test_custom_key_names_via_config(): void
    {
        config(['api-response.keys' => [
            'status'  => 'ok',
            'code'    => 'http_code',
            'message' => 'msg',
            'data'    => 'payload',
        ]]);

        $data = $this->fresh()->success('hello', ['x' => 1])->response()->getData(true);

        $this->assertArrayHasKey('ok',        $data);
        $this->assertArrayHasKey('http_code', $data);
        $this->assertArrayHasKey('msg',       $data);
        $this->assertArrayHasKey('payload',   $data);

        config(['api-response.keys' => []]);
    }
}
