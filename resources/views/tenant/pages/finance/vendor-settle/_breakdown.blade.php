<x-tenant::table :headers="['Item', 'Amount']">
    <tr>
        <td>Product Cost</td>
        <td>${{ number_format((float) data_get($breakdown, 'product_cost', 0), 2) }}</td>
    </tr>
    <tr>
        <td>Shipping Cost</td>
        <td>${{ number_format((float) data_get($breakdown, 'shipping_cost', 0), 2) }}</td>
    </tr>
    <tr>
        <td><strong>Subtotal</strong></td>
        <td><strong>${{ number_format((float) data_get($breakdown, 'subtotal', 0), 2) }}</strong></td>
    </tr>
    <tr>
        <td>
            Gateway Fee
            @if(empty($selected))
                <span class="entity-subtitle">(select a gateway)</span>
            @endif
        </td>
        <td>+${{ number_format((float) data_get($breakdown, 'gateway_fee', 0), 2) }}</td>
    </tr>
    <tr>
        <td><strong>Total to Pay</strong></td>
        <td><strong>${{ number_format((float) data_get($breakdown, 'total', 0), 2) }}</strong></td>
    </tr>
</x-tenant::table>
