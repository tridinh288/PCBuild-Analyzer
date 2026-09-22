import { createContext } from 'react'

/** Admin session; provided by <AuthProvider>, read with useAuth(). */
export const AuthContext = createContext(null)
