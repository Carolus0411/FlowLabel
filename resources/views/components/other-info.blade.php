@props([
    'data'
])
<div class="space-y-4">
    <h2 class="text-lg font-semibold">Other Info</h2>
    <table class="table table-xs">
    <tr>
        <td class="lg:w-[100px]">Created By</td>
        <td>{{ $data->createdBy->name ?? '' }}</td>
    </tr>
    <tr>
        <td>Updated By</td>
        <td>{{ $data->updatedBy->name ?? '' }}</td>
    </tr>
    <tr>
        <td>Created At</td>
        <td>{{ $data->created_at->format('d-m-Y, H:i:s') }}</td>
    </tr>
    <tr>
        <td>Updated At</td>
        <td>{{ $data->updated_at->format('d-m-Y, H:i:s') }}</td>
    </tr>
    </table>
</div>
