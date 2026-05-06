import { useEffect, useRef } from 'react';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js/react-native';
import { buildApiUrl } from '../constants/api';
import { useAuth } from '../contexts/AuthContext';
import { useWebAppRequest } from './useWebAppRequest';

export function useEcho() {
  const echoRef = useRef(null);
  const { session } = useAuth();
  const { activeBaseUrl } = useWebAppRequest();

  useEffect(() => {
    if (!session?.id) return;

    try {
      // Ensure global Pusher exists for Echo
      const PusherConstructor = Pusher;

      // Create Echo instance once per session
      const hostUrl = new URL(activeBaseUrl || 'http://127.0.0.1:8000');

      echoRef.current = new Echo({
        broadcaster: 'pusher',
        key: String(process?.env?.REACT_APP_PUSHER_KEY || process?.env?.VITE_REVERB_APP_KEY || ''),
        wsHost: hostUrl.hostname,
        wsPort: hostUrl.port ? Number(hostUrl.port) : (hostUrl.protocol === 'https:' ? 443 : 80),
        wssPort: hostUrl.port ? Number(hostUrl.port) : (hostUrl.protocol === 'https:' ? 443 : 443),
        forceTLS: hostUrl.protocol === 'https:',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: buildApiUrl('/broadcasting/auth', activeBaseUrl),
        auth: {
          headers: {
            Accept: 'application/json',
          },
          withCredentials: true,
        },
        Pusher: PusherConstructor,
      });
    } catch (err) {
      // fail silently — caller should handle missing echo
      echoRef.current = null;
    }

    return () => {
      try {
        echoRef.current?.disconnect?.();
      } catch (_e) {}
      echoRef.current = null;
    };
  }, [session?.id, activeBaseUrl]);

  return echoRef;
}

export default useEcho;
