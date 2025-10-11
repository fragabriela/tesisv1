<?php

require_once 'vendor/autoload.php';

use App\Models\Tesis;

echo "=== Actualizando Estado de Tesis ===\n\n";

$tesis = Tesis::find(24);

if ($tesis) {
    $tesis->backup_restored = true;
    $tesis->env_configured = true;
    $tesis->backup_restored_at = now();
    $tesis->save();
    
    echo "✅ Tesis actualizada exitosamente:\n";
    echo "   - backup_restored: " . ($tesis->backup_restored ? 'true' : 'false') . "\n";
    echo "   - env_configured: " . ($tesis->env_configured ? 'true' : 'false') . "\n";
    echo "   - backup_restored_at: " . $tesis->backup_restored_at . "\n";
    echo "   - isReadyForDeployment: " . ($tesis->isReadyForDeployment() ? 'true' : 'false') . "\n";
} else {
    echo "❌ Tesis con ID 24 no encontrada\n";
}

?>