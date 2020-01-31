<?php


namespace App\Validator\Constraints;


use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class EmailByDoHValidator extends ConstraintValidator
{

    const DNS_A = 1;
    const DNS_NS = 3;
    const DNS_SOA = 6;
    const DNS_MX = 15;
    const DNS_TXT = 16;

    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof EmailByDoH) {
            throw new UnexpectedTypeException($constraint, EmailByDoH::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string)$value;
        $host = (string)substr($value, strrpos($value, '@') + 1);

        // Check for host DNS resource records
        if ($constraint->checkMX) {
            if (!$this->checkMXByDoH($host)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->addViolation();
            }

            return;
        }

        if ($constraint->checkHost && !$this->checkHostByDoH($host)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();
        }

    }


    /**
     * @param string $host
     * @return bool
     */
    public function checkMXByDoH(string $host): bool
    {
        return $this->checkDoH($host, self::DNS_MX);
    }

    /**
     * @param string $host
     * @return bool
     */
    public function checkHostByDoH(string $host): bool
    {
        return $this->checkDoH($host, self::DNS_A);
    }

    /**
     * @param string $host
     * @param int $type
     * @return bool
     */
    public function checkDoH(string $host, int $type): bool
    {
        static $result = [];

        if (empty(trim($host))) {
            return false;
        }

        if (!in_array($type, [
            self::DNS_A,
            self::DNS_MX,
            self::DNS_NS,
            self::DNS_SOA,
            self::DNS_TXT
        ], true)) {
            return false;
        }

        if (isset($result[$host])) {
            return isset($result[$host][$type]);
        }

        try {
            $client = HttpClient::create();
            $response = $client->request('GET', 'https://dns.google/resolve', [
                'query' => [
                    'name' => urlencode($host),
                    'type' => 255,
                    'do' => true
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return false;
            }

            $contents = $response->getContent();
        } catch (ExceptionInterface $e) {
            return false;
        }
        /**
         * https://developers.google.com/speed/public-dns/docs/doh
         * https://developers.google.com/speed/public-dns/docs/doh/json
         * https://developers.google.com/speed/public-dns/docs/secure-transports#doh
         *
         * 以下のようなJsonデータが戻ってくる
         * Questionが問い合わせ
         * AnswerがDNSからの回答
         * type
         *  1 : a
         *  2 : ns
         *  6 : soa
         * 15 : MX
         * 16 : TXT
         * {
         * "Status": 0,
         * "TC": false,
         * "RD": true,
         * "RA": true,
         * "AD": false,
         * "CD": false,
         * "Question": [
         * {
         * "name": "hirotae.com.",
         * "type": 255
         * }
         * ],
         * "Answer": [
         * {
         * "name": "hirotae.com.",
         * "type": 1,
         * "TTL": 1199,
         * "data": "150.95.8.211"
         * },
         * {
         * "name": "hirotae.com.",
         * "type": 2,
         * "TTL": 1199,
         * "data": "ns11.value-domain.com."
         * },
         * {
         * "name": "hirotae.com.",
         * "type": 2,
         * "TTL": 1199,
         * "data": "ns12.value-domain.com."
         * },
         * {
         * "name": "hirotae.com.",
         * "type": 2,
         * "TTL": 1199,
         * "data": "ns13.value-domain.com."
         * },
         * {
         * "name": "hirotae.com.",
         * "type": 6,
         * "TTL": 2559,
         * "data": "ns13.value-domain.com. hostmaster.hirotae.com. 1580448474 16384 2048 1048576 2560"
         * },
         * {
         * "name": "hirotae.com.",
         * "type": 15,
         * "TTL": 1199,
         * "data": "10 hirotae.com."
         * },
         * {
         * "name": "hirotae.com.",
         * "type": 16,
         * "TTL": 1199,
         * "data": "\"v=spf1 ip4:150.95.8.211 +ipv4:59.106.27.210 ~all\""
         * }
         * ],
         * "Additional": [],
         * "Comment": "Response from 54.199.222.7."
         * }
         */
        $dnsRecode = json_decode($contents, true);

        if ($dnsRecode['Status'] != 0) {
            return false;
        }

        if (!isset($dnsRecode['Answer']) || !is_array($dnsRecode['Answer'])) {
            return false;
        }

        $hAnswers = $dnsRecode['Answer'];
        foreach ($hAnswers as $hAnswer) {
            $qtype = $hAnswer['type'];
            $result[$host][$qtype] = $hAnswer;
        }

        return isset($result[$host][$type]);
    }
}