<?php
namespace Opencart\Admin\Model\Sale;
class Subscription extends \Opencart\System\Engine\Model {
	public function getSubscriptions(array $data): array {
		$sql = "SELECT `s`.`subscription_id`, `s`.`order_id`, `s`.`reference`, `s`.`status`, `s`.`date_added`, CONCAT(o.`firstname`, ' ', o.`lastname`) AS customer FROM `" . DB_PREFIX . "subscription` `s` LEFT JOIN `" . DB_PREFIX . "order` `o` ON (`s`.`order_id` = `o`.`order_id`)";

		$implode = [];

		if (!empty($data['filter_subscription_id'])) {
			$implode[] = "`s`.`subscription_id` = '" . (int)$data['filter_subscription_id'] . "'";
		}

		if (!empty($data['filter_order_id'])) {
			$implode[] = "`s`.`order_id` = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_reference'])) {
			$implode[] = "`s`.`reference` LIKE '" . $this->db->escape((string)$data['filter_reference']) . "%'";
		}

		if (!empty($data['filter_customer'])) {
			$implode[] = "CONCAT(o.`firstname`, ' ', o.`lastname`) LIKE '" . $this->db->escape((string)$data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_status'])) {
			$implode[] = "`s`.`status` = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$implode[] = "DATE(`s`.`date_added`) = DATE('" . $this->db->escape((string)$data['filter_date_added']) . "')";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$sort_data = [
			's.subscription_id',
			's.order_id',
			's.reference',
			'customer',
			's.status',
			's.date_added'
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY `s`.`subscription_id`";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getSubscription(int $subscription_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "subscription_transaction` WHERE `subscription_id` = '" . (int)$subscription_id . "'");

		return $query->row;
	}

	public function getTotalSubscriptions(array $data = []): int {
		$sql = "SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "subscription` `s` LEFT JOIN `" . DB_PREFIX . "order` `o` ON (`s`.`order_id` = o.`order_id`)";

		$implode = [];

		if (!empty($data['filter_subscription_id'])) {
			$implode[] .= "`s`.`subscription_id` = '" . (int)$data['filter_subscription_id'] . "'";
		}

		if (!empty($data['filter_order_id'])) {
			$implode[] .= "`or`.`order_id` = '" . (int)$data['filter_order_id'] . "'";
		}

		if (!empty($data['filter_payment_reference'])) {
			$implode[] .= "`or`.`reference` LIKE '" . $this->db->escape((string)$data['filter_reference']) . "%'";
		}

		if (!empty($data['filter_customer'])) {
			$implode[] .= "CONCAT(o.`firstname`, ' ', o.`lastname`) LIKE '" . $this->db->escape((string)$data['filter_customer']) . "%'";
		}

		if (!empty($data['filter_status'])) {
			$implode[] .= "`or`.`status` = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$implode[] .= "DATE(`or`.`date_added`) = DATE('" . $this->db->escape((string)$data['filter_date_added']) . "')";
		}

		if ($implode) {
			$sql .= " WHERE " . implode(" AND ", $implode);
		}

		$query = $this->db->query($sql);

		return (int)$query->row['total'];
	}

	public function getTransactions(int $subscription_id): array {
		$transactions = [];

		$query = $this->db->query("SELECT `order_id`, `amount`, `type`, `date_added` FROM `" . DB_PREFIX . "subscription_transaction` WHERE `subscription_id` = '" . (int)$subscription_id . "' ORDER BY `date_added` DESC");

		foreach ($query->rows as $result) {
			switch ($result['type']) {
				case 0:
					$type = $this->language->get('text_transaction_date_added');
					break;
				case 1:
					$type = $this->language->get('text_transaction_payment');
					break;
				case 2:
					$type = $this->language->get('text_transaction_outstanding_payment');
					break;
				case 3:
					$type = $this->language->get('text_transaction_skipped');
					break;
				case 4:
					$type = $this->language->get('text_transaction_failed');
					break;
				case 5:
					$type = $this->language->get('text_transaction_cancelled');
					break;
				case 6:
					$type = $this->language->get('text_transaction_suspended');
					break;
				case 7:
					$type = $this->language->get('text_transaction_suspended_failed');
					break;
				case 8:
					$type = $this->language->get('text_transaction_outstanding_failed');
					break;
				case 9:
					$type = $this->language->get('text_transaction_expired');
					break;
				default:
					$type = '';
					break;
			}

			$transactions[] = [
				'date_added' => $result['date_added'],
				'amount'     => $result['amount'],
				'type'       => $type,
				'order_id'   => $result['order_id']
			];
		}

		return $transactions;
	}

	private function getStatus(int $status): string {
		switch ($status) {
			case 1:
				$result = $this->language->get('text_status_inactive');
				break;
			case 2:
				$result = $this->language->get('text_status_active');
				break;
			case 3:
				$result = $this->language->get('text_status_suspended');
				break;
			case 4:
				$result = $this->language->get('text_status_cancelled');
				break;
			case 5:
				$result = $this->language->get('text_status_expired');
				break;
			case 6:
				$result = $this->language->get('text_status_pending');
				break;
			default:
				$result = '';
				break;
		}

		return $result;
	}
}