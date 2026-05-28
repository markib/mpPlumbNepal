import React from 'react';
import { useTranslation } from 'react-i18next';
import { MapContainer, TileLayer, Marker, useMapEvents } from 'react-leaflet';
import type { LatLngExpression, LeafletMouseEvent } from 'leaflet';
import type { BookingFormValues }
  from '../features/booking/types/booking';

interface MapPinAddressProps {
  value: BookingFormValues;
  onChange: (value: BookingFormValues) => void;
}

const LocationMarker: React.FC<MapPinAddressProps> = ({ value, onChange }) => {
  const position: LatLngExpression = [value.latitude, value.longitude];

  useMapEvents({
    click(e: LeafletMouseEvent) {
      onChange({ ...value, latitude: e.latlng.lat, longitude: e.latlng.lng });
    },
  });

  return <Marker position={position} />;
};

const MapPinAddress: React.FC<MapPinAddressProps> = ({ value, onChange }) => {
  const { t } = useTranslation();
  const position: LatLngExpression = [value.latitude, value.longitude];

  return (
    <div className="space-y-3">
      <p className="text-sm text-slate-600">{t('pinOnMapInstruction')}</p>
      <div className="h-72 w-full overflow-hidden rounded-xl border border-slate-200 shadow-inner">
        <MapContainer center={position} zoom={13} className="h-full w-full">
          <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
          <LocationMarker value={value} onChange={onChange} />
        </MapContainer>
      </div>
      <div className="flex items-center gap-2 text-xs text-slate-500">
        <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
        </svg>
        <span>{t('currentCoordinates')}: {value.latitude.toFixed(5)}, {value.longitude.toFixed(5)}</span>
      </div>
    </div>
  );
};

export default MapPinAddress;
