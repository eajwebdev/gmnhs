<?php

namespace Tests\Feature;

use App\Models\Accountable;
use App\Models\EnduserProperty;
use App\Models\Item;
use App\Models\Office;
use App\Models\Purchases;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Generates real PDF / Excel output (wkhtmltopdf and dompdf) against the
 * configured database. Everything runs inside a rolled-back transaction.
 */
class ReportOutputTest extends TestCase
{
    use DatabaseTransactions;

    private Office $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::where('role', 'Administrator')->firstOrFail());
        $this->office = Office::where('office_code', '!=', '0000')->whereIn('id', Accountable::pluck('off_id'))->firstOrFail();
        $this->releaseOneItem();
    }

    private function releaseOneItem(): void
    {
        $purchase = Purchases::whereColumn('qty_release', '<', 'qty')->firstOrFail();
        Item::whereKey($purchase->item_id)->update(['ct' => 1]);

        $get = $this->get(route('purchaseReleaseGet', $purchase->id))->json();
        $next = $this->get('/purchases/check-next-number/'.preg_replace('/^[^-]+-/', '', $get['pcode']).'/'.$this->office->office_code)->json();

        $this->post(route('purchaseReleasePost'), [
            'purchase_id' => $purchase->id, 'office_id' => $this->office->id, 'person_accnt' => $next['accountables'][0]['id'],
            'qty' => 1, 'date_acquired' => now()->toDateString(), 'itemnum' => $next['next_item_number'],
            'property_no_generated' => $get['pcode'].'-'.$next['next_item_number'].'-'.$this->office->office_code,
        ])->assertSessionHas('success');

        $this->assertTrue(EnduserProperty::where('purch_id', $purchase->id)->exists());
    }

    private function assertPdf($response, string $label): void
    {
        $this->assertSame(200, $response->getStatusCode(), $label.': '.($response->exception ?? null)?->getMessage());
        $body = $response->baseResponse instanceof \Symfony\Component\HttpFoundation\StreamedResponse
            ? $response->streamedContent()
            : $response->getContent();
        $this->assertStringStartsWith('%PDF', $body, $label.' did not return a PDF');
    }

    private function assertExcel($response, string $label): void
    {
        $this->assertSame(200, $response->getStatusCode(), $label.': '.($response->exception ?? null)?->getMessage());
        $this->assertStringStartsWith('PK', $response->streamedContent(), $label.' did not return an xlsx file');
    }

    public function test_reports_page_generates_every_type_as_pdf_and_excel(): void
    {
        foreach ([1, 2, 3, 4, 5] as $type) {
            $filters = ['report_type' => $type, 'office_id' => ['All'], 'date_range' => ''];

            $this->assertPdf($this->post(route('generateReport'), $filters + ['format' => 'pdf']), "type $type pdf");
            $this->assertExcel($this->post(route('generateReport'), $filters + ['format' => 'excel']), "type $type excel");

            // Every optional column and page option ticked, with a date range
            $allOptions = $filters + [
                'date_range' => '2000-01-01 - '.now()->toDateString(),
                'columns' => ['location', 'serial_number', 'date_acquired'],
                'balance_bforward' => 1, 'eachpage_subtotal' => 1, 'grand_total' => 1, 'eachpage_header' => 1, 'eachpage_footer' => 1,
            ];
            $this->assertPdf($this->post(route('generateReport'), $allOptions + ['format' => 'pdf']), "type $type pdf, all options");
            $this->assertExcel($this->post(route('generateReport'), $allOptions + ['format' => 'excel']), "type $type excel, all options");
        }
    }

    public function test_legacy_report_urls_work(): void
    {
        $this->get('/reports/1')->assertRedirect(route('reportForm', ['type' => 1]));
        $this->get('/reports/rpcsep/option')->assertRedirect(route('reportForm', ['type' => 2]));
        $this->get('/reports/ics/option')->assertRedirect(route('reportForm', ['type' => 3]));
        $this->get('/reports/par/option')->assertRedirect(route('reportForm', ['type' => 4]));
        $this->get('/reports/unserviceable-form')->assertRedirect(route('reportForm', ['type' => 5]));
        $this->get(route('reportForm', ['type' => 3]))->assertOk()->assertSee('<option value="3" selected', false);

        $this->assertPdf($this->get('/reports/rpcppe/reports/rpcppe?office_id=All&file_type=PDF'), 'legacy rpcppe');
        $this->assertPdf($this->post('/reports/ics/reports/ics', ['office_id' => 'All', 'start_date_acquired' => '2000-01-01', 'end_date_acquired' => now()->toDateString()]), 'legacy ics');
        $this->assertExcel($this->post('/reports/par/reports/par', ['office_id' => 'All', 'file_type' => 'EXCEL']), 'legacy par excel');
        $this->assertPdf($this->post('/reports/unserviceable-report', ['office_id' => 'All']), 'legacy unserviceable');

        $this->post('/reports/allgenoption', ['office_id' => $this->office->id, 'report_type' => 1])->assertOk()->assertJsonStructure(['selectitems']);
        $this->post('/reports/ics/icsitemList', ['office_id' => 'All'])->assertOk()->assertJsonStructure(['selectitems']);
        $this->get('/reports/display-item/1')->assertOk()->assertJsonStructure(['selectitems']);
    }

    public function test_sticker_and_return_slip_pdfs(): void
    {
        $this->assertPdf($this->get(route('propertiesStickerTemplatePDF')), 'blank stickers');
        $this->assertPdf($this->get(route('stickerReadPdf', $this->office->id)), 'office stickers');
        $this->assertPdf($this->get(route('stickers.pdf', ['office' => $this->office->id, 'range' => '1-1000'])), 'sticker download');
        $this->get('/properties/list/ppe')->assertRedirect(route('propertiesRead', 3));

        $this->assertPdf($this->get(route('returnSlips.iirupReport.generate', ['format' => 'pdf'])), 'iirup pdf');
        $this->assertExcel($this->get(route('returnSlips.iirupReport.generate', ['format' => 'excel'])), 'iirup excel');
    }
}
