import { render, screen, waitFor, act, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { vi } from 'vitest';
import BookingPage from './BookingPage';

vi.mock('react-i18next', () => ({
  useTranslation: () => ({ t: (key: string) => key }),
}));

vi.mock('../components/MapPinAddress', () => ({
  default: ({ onChange }: { onChange: (next: unknown) => void }) => (
    <input
      data-testid="map-pin"
      onChange={(event) => onChange(JSON.parse((event.target as HTMLInputElement).value))}
    />
  ),
}));

describe('BookingPage', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
    global.fetch = vi.fn() as unknown as typeof fetch;
  });

  it('disables the Request Service button when service_type_id is invalid or no service types are available', async () => {
    const fetchMock = vi.fn()
      .mockResolvedValueOnce({
        ok: true,
        headers: { get: () => 'application/json' },
        json: async () => [],
      } as unknown as Response);

    global.fetch = fetchMock as unknown as typeof fetch;

    render(<BookingPage />);

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /requestService/i })).toBeDisabled();
    });
  });

  it('submits the booking form when the Request Service button is clicked', async () => {
    const fetchMock = vi.fn()
      .mockResolvedValueOnce({
        ok: true,
        headers: { get: () => 'application/json' },
        json: async () => [{ id: 1, name: 'Plumbing' }],
      } as unknown as Response)
      .mockResolvedValueOnce({
        ok: true,
        headers: { get: () => 'application/json' },
        json: async () => ({ booking: { id: 1 } }),
      } as unknown as Response);

    global.fetch = fetchMock as unknown as typeof fetch;

    render(<BookingPage />);

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /requestService/i })).toBeEnabled();
    });

    await act(async () => {
      await userEvent.click(screen.getByRole('button', { name: /requestService/i }));
    });

    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledTimes(2);
      expect(fetchMock).toHaveBeenLastCalledWith(expect.stringContaining('/api/v1/bookings'), expect.objectContaining({ method: 'POST' }));
    });
  });

  it('shows a backend validation error when the API returns 422 with JSON payload', async () => {
    const fetchMock = vi.fn()
      .mockResolvedValueOnce({
        ok: true,
        headers: { get: () => 'application/json' },
        json: async () => [{ id: 1, name: 'Plumbing' }],
      } as unknown as Response)
      .mockResolvedValueOnce({
        ok: false,
        status: 422,
        headers: { get: () => 'application/json' },
        json: async () => ({
          message: 'The given data was invalid.',
          errors: { service_type_id: ['The selected service type id is invalid.'] },
        }),
      } as unknown as Response);

    global.fetch = fetchMock as unknown as typeof fetch;

    render(<BookingPage />);

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /requestService/i })).toBeEnabled();
    });

    await act(async () => {
      await userEvent.click(screen.getByRole('button', { name: /requestService/i }));
    });

    await waitFor(() => {
      expect(screen.getByText('The selected service type id is invalid.')).toBeInTheDocument();
    });
  });

  it('shows missing payment method validation error from backend 422 response', async () => {
    const fetchMock = vi.fn()
      .mockResolvedValueOnce({
        ok: true,
        headers: { get: () => 'application/json' },
        json: async () => [{ id: 1, name: 'Plumbing' }],
      } as unknown as Response)
      .mockResolvedValueOnce({
        ok: false,
        status: 422,
        headers: { get: () => 'application/json' },
        json: async () => ({
          message: 'The given data was invalid.',
          errors: { payment_method: ['The payment method field is required.'] },
        }),
      } as unknown as Response);

    global.fetch = fetchMock as unknown as typeof fetch;

    render(<BookingPage />);

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /requestService/i })).toBeEnabled();
    });

    await act(async () => {
      await userEvent.click(screen.getByRole('button', { name: /requestService/i }));
    });

    await waitFor(() => {
      expect(screen.getByText('The payment method field is required.')).toBeInTheDocument();
    });
  });

  it('shows invalid coordinate validation errors from backend 422 response', async () => {
    const fetchMock = vi.fn()
      .mockResolvedValueOnce({
        ok: true,
        headers: { get: () => 'application/json' },
        json: async () => [{ id: 1, name: 'Plumbing' }],
      } as unknown as Response)
      .mockResolvedValueOnce({
        ok: false,
        status: 422,
        headers: { get: () => 'application/json' },
        json: async () => ({
          message: 'The given data was invalid.',
          errors: {
            latitude: ['The latitude must be a number.'],
            longitude: ['The longitude must be a number.'],
          },
        }),
      } as unknown as Response);

    global.fetch = fetchMock as unknown as typeof fetch;

    render(<BookingPage />);

    await waitFor(() => {
      expect(screen.getByRole('button', { name: /requestService/i })).toBeEnabled();
    });

    const mapPinInput = screen.getByTestId('map-pin');
    fireEvent.change(mapPinInput, { target: { value: JSON.stringify({
      latitude: 'invalid-latitude',
      longitude: 'invalid-longitude',
      service_type_id: 1,
      is_emergency: false,
      payment_method: 'cod',
    }) } });

    await act(async () => {
      await userEvent.click(screen.getByRole('button', { name: /requestService/i }));
    });

    await waitFor(() => {
      expect(screen.getByText('The latitude must be a number. The longitude must be a number.')).toBeInTheDocument();
    });
  });
});





