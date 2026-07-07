<?php
// controlador/mensajeriaController.php

require_once __DIR__ . '/../modelo/mensajeriaModel.php';

class MensajeriaController {

    public function index(): void {
        $desde = trim($_GET['desde'] ?? '') ?: date('Y-m-d', strtotime('-7 days'));
        $hasta = trim($_GET['hasta'] ?? '') ?: date('Y-m-d');

        $model   = new MensajeriaModel();
        $uso     = $model->obtenerUso($desde, $hasta);
        $balance = $model->obtenerBalance();

        require_once __DIR__ . '/../vista/mensajeria/index.php';
    }

    public function csv(): void {
        $desde = trim($_GET['desde'] ?? '') ?: date('Y-m-d', strtotime('-7 days'));
        $hasta = trim($_GET['hasta'] ?? '') ?: date('Y-m-d');

        $model = new MensajeriaModel();
        $uso   = $model->obtenerUso($desde, $hasta);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="mensajeria_' . $desde . '_a_' . $hasta . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Fecha', 'Teléfono', 'Comercio', 'Tipo', 'Mensaje', 'Costo', 'Estado']);
        foreach ($uso['actividad'] as $it) {
            fputcsv($out, [
                $it['createdAt']       ?? '',
                $it['telefono']        ?? '',
                $it['comercio_nombre'] ?? '',
                $it['type']            ?? '',
                $it['sampleMessage']   ?? '',
                $it['amount']          ?? 0,
                $it['status']          ?? (($it['isSent'] ?? false) ? 'sent' : 'pending'),
            ]);
        }
        fclose($out);
        exit;
    }
}
