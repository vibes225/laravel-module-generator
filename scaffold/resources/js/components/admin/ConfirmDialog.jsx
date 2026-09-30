import Button from './Button';
import Modal from './Modal';

export default function ConfirmDialog({
    open,
    onCancel,
    onConfirm,
    title = 'Confirmer',
    message,
    confirmLabel = 'Confirmer',
    cancelLabel = 'Annuler',
    tone = 'danger',
    processing = false,
}) {
    return (
        <Modal
            open={open}
            onClose={onCancel}
            title={title}
            size="sm"
            footer={
                <>
                    <Button variant="secondary" onClick={onCancel} disabled={processing}>
                        {cancelLabel}
                    </Button>
                    <Button variant={tone} onClick={onConfirm} loading={processing}>
                        {confirmLabel}
                    </Button>
                </>
            }
        >
            {message}
        </Modal>
    );
}
