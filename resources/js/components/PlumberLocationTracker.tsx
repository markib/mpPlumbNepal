import React, { useState, useEffect } from 'react';
import { apiUrl } from '../utils/api';

interface LocationData {
  latitude: number;
  longitude: number;
  accuracy?: number;
  speed?: number;
  heading?: number;
}

interface PlumberLocationTrackerProps {
  bookingId?: number;
  onLocationUpdate?: (location: LocationData) => void;
}

export const PlumberLocationTracker: React.FC<PlumberLocationTrackerProps> = ({
  bookingId,
  onLocationUpdate
}) => {
  const [isTracking, setIsTracking] = useState(false);
  const [currentLocation, setCurrentLocation] = useState<LocationData | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [watchId, setWatchId] = useState<number | null>(null);

  const updateLocation = async (position: GeolocationPosition) => {
    const locationData: LocationData = {
      latitude: position.coords.latitude,
      longitude: position.coords.longitude,
      accuracy: position.coords.accuracy,
      speed: position.coords.speed || undefined,
      heading: position.coords.heading || undefined,
    };

    setCurrentLocation(locationData);
    onLocationUpdate?.(locationData);

    // Send location to server
    try {
      const response = await fetch(apiUrl('/api/v1/dispatch/location'), {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(locationData),
      });

      if (!response.ok) {
        throw new Error('Failed to update location');
      }
    } catch (err) {
      console.error('Location update failed:', err);
      setError('Failed to update location on server');
    }
  };

  const startTracking = () => {
    if (!navigator.geolocation) {
      setError('Geolocation is not supported by this browser');
      return;
    }

    setError(null);
    setIsTracking(true);

    const id = navigator.geolocation.watchPosition(
      updateLocation,
      (err) => {
        console.error('Geolocation error:', err);
        setError(`Location error: ${err.message}`);
        setIsTracking(false);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 30000,
      }
    );

    setWatchId(id);
  };

  const stopTracking = () => {
    if (watchId !== null) {
      navigator.geolocation.clearWatch(watchId);
      setWatchId(null);
    }
    setIsTracking(false);
  };

  useEffect(() => {
    return () => {
      if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
      }
    };
  }, [watchId]);

  return (
    <div className="p-4 bg-white rounded-xl shadow-sm border border-slate-200">
      <h3 className="text-lg font-semibold mb-4 text-slate-800">Location Tracking</h3>

      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <span className="text-sm text-slate-600">
            Status: {isTracking ? 'Active' : 'Inactive'}
          </span>
          <div className={`w-3 h-3 rounded-full ${isTracking ? 'bg-green-500' : 'bg-gray-400'}`}></div>
        </div>

        {currentLocation && (
          <div className="text-sm text-slate-600 space-y-1">
            <p>Lat: {currentLocation.latitude.toFixed(6)}</p>
            <p>Lng: {currentLocation.longitude.toFixed(6)}</p>
            {currentLocation.accuracy && <p>Accuracy: ±{currentLocation.accuracy.toFixed(0)}m</p>}
            {currentLocation.speed && <p>Speed: {(currentLocation.speed * 3.6).toFixed(1)} km/h</p>}
          </div>
        )}

        {error && (
          <div className="text-sm text-red-600 bg-red-50 p-2 rounded">
            {error}
          </div>
        )}

        <div className="flex gap-2">
          {!isTracking ? (
            <button
              onClick={startTracking}
              className="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
            >
              Start Tracking
            </button>
          ) : (
            <button
              onClick={stopTracking}
              className="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
            >
              Stop Tracking
            </button>
          )}
        </div>
      </div>
    </div>
  );
};