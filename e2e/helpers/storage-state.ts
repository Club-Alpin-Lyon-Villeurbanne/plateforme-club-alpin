import fs from 'fs';
import path from 'path';

const authDir = path.join(__dirname, '..', '.auth');
fs.mkdirSync(authDir, { recursive: true });

export const STORAGE_STATE = {
  admin: path.join(authDir, 'admin.json'),
  redacteur: path.join(authDir, 'redacteur.json'),
  encadrant: path.join(authDir, 'encadrant.json'),
  respComm: path.join(authDir, 'resp-comm.json'),
};
