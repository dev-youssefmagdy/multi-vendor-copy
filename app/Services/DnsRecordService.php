<?php

namespace App\Services;

use App\Models\DnsRecord;
use Illuminate\Support\Facades\DB;

class DnsRecordService
{
    public function save(array $attributes, ?DnsRecord $record = null): DnsRecord
    {
        return DB::transaction(function () use ($attributes, $record) {
            $record ??= new DnsRecord();
            $record->fill([
                'type'        => strtoupper(trim((string) $attributes['type'])),
                'name'        => trim((string) $attributes['name']),
                'value'       => trim((string) $attributes['value']),
                'ttl'         => (int) ($attributes['ttl'] ?? 3600),
                'priority'    => filled($attributes['priority'] ?? null) ? (int) $attributes['priority'] : null,
                'description' => $attributes['description'] ?? null,
                'is_required' => (bool) ($attributes['is_required'] ?? true),
            ]);
            $record->save();

            return $record;
        });
    }

    public function delete(DnsRecord $record): void
    {
        $record->delete();
    }

    /**
     * Check whether the given domain has DNS records matching our required records.
     * Returns an array: ['connected' => bool, 'checks' => [['record' => DnsRecord, 'ok' => bool], ...]]
     */
    public function checkDomain(string $domain, \Illuminate\Database\Eloquent\Collection $records): array
    {
        $checks = [];
        $allOk = true;

        foreach ($records as $record) {
            $ok = $this->verifyRecord($record, $domain);
            if (!$ok) {
                $allOk = false;
            }
            $checks[] = ['record' => $record, 'ok' => $ok];
        }

        return ['connected' => $allOk && count($checks) > 0, 'checks' => $checks];
    }

    private function verifyRecord(DnsRecord $record, string $domain): bool
    {
        try {
            $host = $record->name === '@' ? $domain : $record->name . '.' . $domain;

            return match ($record->type) {
                'A'     => $this->checkA($host, $record->value),
                'AAAA'  => $this->checkAAAA($host, $record->value),
                'CNAME' => $this->checkCname($host, $record->value),
                'MX'    => $this->checkMx($host, $record->value),
                'TXT'   => $this->checkTxt($host, $record->value),
                default => false,
            };
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkA(string $host, string $expectedIp): bool
    {
        $records = @dns_get_record($host, DNS_A);
        if (!$records) {
            return false;
        }
        foreach ($records as $r) {
            if (isset($r['ip']) && $r['ip'] === $expectedIp) {
                return true;
            }
        }
        return false;
    }

    private function checkAAAA(string $host, string $expectedIp): bool
    {
        $records = @dns_get_record($host, DNS_AAAA);
        if (!$records) {
            return false;
        }
        foreach ($records as $r) {
            if (isset($r['ipv6']) && strtolower($r['ipv6']) === strtolower($expectedIp)) {
                return true;
            }
        }
        return false;
    }

    private function checkCname(string $host, string $expectedTarget): bool
    {
        $records = @dns_get_record($host, DNS_CNAME);
        if (!$records) {
            return false;
        }
        $target = rtrim(strtolower($expectedTarget), '.');
        foreach ($records as $r) {
            if (isset($r['target']) && rtrim(strtolower($r['target']), '.') === $target) {
                return true;
            }
        }
        return false;
    }

    private function checkMx(string $host, string $expectedTarget): bool
    {
        $records = @dns_get_record($host, DNS_MX);
        if (!$records) {
            return false;
        }
        $target = rtrim(strtolower($expectedTarget), '.');
        foreach ($records as $r) {
            if (isset($r['target']) && rtrim(strtolower($r['target']), '.') === $target) {
                return true;
            }
        }
        return false;
    }

    private function checkTxt(string $host, string $expectedValue): bool
    {
        $records = @dns_get_record($host, DNS_TXT);
        if (!$records) {
            return false;
        }
        foreach ($records as $r) {
            $entries = $r['entries'] ?? [$r['txt'] ?? ''];
            foreach ($entries as $entry) {
                if (str_contains($entry, $expectedValue)) {
                    return true;
                }
            }
        }
        return false;
    }
}
