<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/InvoiceModel.php';

class InvoiceController {
    private $conn;
    private $invoiceModel;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->invoiceModel = new InvoiceModel($conn);
    }

    public function getAllInvoices() {
        return $this->invoiceModel->getAllInvoices();
    }

    public function getUserInvoices($userId) {
        return $this->invoiceModel->getUserInvoices($userId);
    }

    public function getInvoiceDetails($invoiceId, $userId) {
        return $this->invoiceModel->getInvoiceDetails($invoiceId, $userId);
    }

    public function getAggregatedInvoiceDetails($invoiceId) {
        return $this->invoiceModel->getAggregatedInvoiceDetails($invoiceId);
    }

    public function getTransactionDetails($invoiceId) {
        return $this->invoiceModel->getTransactionDetails($invoiceId);
    }

    public function getInvoiceByOrderId($orderId) {
        return $this->invoiceModel->getInvoiceByOrderId($orderId);
    }

    public function updateInvoiceStatus($invoiceId, $status, $userId) {
        return $this->invoiceModel->updateInvoiceStatus($invoiceId, $status, $userId);
    }

    public function deleteInvoice($invoiceId, $userId) {
        return $this->invoiceModel->deleteInvoice($invoiceId, $userId);
    }
}
?>