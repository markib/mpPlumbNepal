interface Props {
    value: number;

    options: any[];

    onChange: (value: number) => void;
}

const ServiceTypeSelect = ({
    value,
    options,
    onChange,
}: Props) => {
    return (
        <select
            value={value}
            onChange={(e) =>
                onChange(Number(e.target.value))
            }
            className="w-full border rounded-lg p-3"
        >
            {options.map((option) => (
                <option
                    key={option.id}
                    value={option.id}
                >
                    {option.name}
                </option>
            ))}
        </select>
    );
};

export default ServiceTypeSelect;