import Input from './Input';

// Champ natif : `type` vaut "date" (défaut) ou "datetime-local".
// Accepte les valeurs renvoyées par Laravel (AAAA-MM-JJ, AAAA-MM-JJ HH:MM:SS, ISO 8601).
function toInputValue(value, type) {
    if (!value) {
        return '';
    }

    const text = String(value);

    if (type === 'datetime-local') {
        return text.replace(' ', 'T').slice(0, 16);
    }

    return text.slice(0, 10);
}

export default function DatePicker({ value, type = 'date', ...props }) {
    return <Input type={type} value={toInputValue(value, type)} {...props} />;
}
