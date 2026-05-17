#!/usr/bin/env node
const { spawn, execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const cwd = process.cwd();
const pidFile = path.join(cwd, '.amzbelly.pid');
const serverFile = path.join(cwd, 'server.js');
const gitFolder = path.join(cwd, '.git');

function showHelp() {
  console.log('amzbelly - Comando de gerenciamento do projeto');
  console.log('');
  console.log('Uso: amzbelly <comando> [subcomando]');
  console.log('');
  console.log('Comandos:');
  console.log('  help              Mostra esta ajuda');
  console.log('  serve on          Inicia server.js em segundo plano');
  console.log('  serve off         Encerra o servidor iniciado por amzbelly');
  console.log('  status            Mostra se o servidor está rodando');
  console.log('  update            Atualiza o projeto com git pull e reinicia se o servidor estiver ativo');
  console.log('  phpserve          Inicia PHP embutido em http://localhost:8000');
  console.log('');
  console.log('Exemplo:');
  console.log('  amzbelly serve on');
  console.log('  amzbelly serve off');
  console.log('  amzbelly update');
}

function runCommand(cmd, args, options = {}) {
  return new Promise((resolve, reject) => {
    const child = spawn(cmd, args, { stdio: 'inherit', shell: true, ...options });
    child.on('close', code => {
      if (code === 0) resolve();
      else reject(new Error(`${cmd} ${args.join(' ')} saiu com código ${code}`));
    });
    child.on('error', reject);
  });
}

async function gitPull() {
  if (!fs.existsSync(gitFolder)) {
    console.error('Esta pasta não parece ser um repositório Git. Clone o projeto do GitHub primeiro.');
    process.exit(1);
  }
  await runCommand('git', ['pull'], { cwd });
}

function readPidFile() {
  if (!fs.existsSync(pidFile)) return null;
  try {
    return parseInt(fs.readFileSync(pidFile, 'utf8').trim(), 10);
  } catch {
    return null;
  }
}

function isProcessRunning(pid) {
  if (!pid || Number.isNaN(pid)) return false;
  try {
    process.kill(pid, 0);
    return true;
  } catch (error) {
    return false;
  }
}

function writePid(pid) {
  fs.writeFileSync(pidFile, String(pid), 'utf8');
}

function removePidFile() {
  if (fs.existsSync(pidFile)) {
    fs.unlinkSync(pidFile);
  }
}

function killProcess(pid) {
  try {
    process.kill(pid);
    return true;
  } catch (error) {
    if (error.code === 'ESRCH') return false;
    if (process.platform === 'win32') {
      try {
        execSync(`taskkill /PID ${pid} /F /T`, { stdio: 'ignore' });
        return true;
      } catch {
        return false;
      }
    }
    return false;
  }
}

function serveOn() {
  if (!fs.existsSync(serverFile)) {
    console.error('Não foi possível encontrar server.js na pasta atual.');
    process.exit(1);
  }

  const existingPid = readPidFile();
  if (existingPid && isProcessRunning(existingPid)) {
    console.log(`Servidor já está rodando (PID ${existingPid}). Use 'amzbelly serve off' primeiro.`);
    return;
  }

  const child = spawn('node', ['server.js'], {
    cwd,
    detached: true,
    stdio: 'ignore',
    shell: true,
  });
  child.unref();
  writePid(child.pid);
  console.log(`Servidor iniciado em segundo plano com PID ${child.pid}.`);
}

function serveOff() {
  const pid = readPidFile();
  if (!pid) {
    console.log('Nenhum servidor iniciado pelo amzbelly foi encontrado.');
    return;
  }
  if (!isProcessRunning(pid)) {
    console.log(`Processo ${pid} não está mais em execução. Limpando estado.`);
    removePidFile();
    return;
  }
  const killed = killProcess(pid);
  if (killed) {
    console.log(`Servidor encerrado (PID ${pid}).`);
    removePidFile();
  } else {
    console.error(`Não foi possível encerrar o processo ${pid}.`);
    process.exit(1);
  }
}

function status() {
  const pid = readPidFile();
  if (pid && isProcessRunning(pid)) {
    console.log(`Servidor está rodando com PID ${pid}.`);
  } else {
    console.log('Servidor não está rodando.');
  }
}

function phpServe() {
  console.log('Iniciando servidor PHP embutido em http://localhost:8000');
  runCommand('php', ['-S', 'localhost:8000'], { cwd }).catch(err => {
    console.error('Erro ao iniciar o PHP embutido:', err.message);
    process.exit(1);
  });
}

async function updateProject() {
  const pid = readPidFile();
  const wasRunning = pid && isProcessRunning(pid);
  if (wasRunning) {
    console.log('Servidor ativo detectado. Encerrando antes de atualizar...');
    serveOff();
  }
  console.log('Executando git pull...');
  try {
    await gitPull();
    console.log('Projeto atualizado com sucesso.');
    if (wasRunning) {
      console.log('Religando o servidor...');
      serveOn();
    }
  } catch (err) {
    console.error('Erro ao atualizar o projeto:', err.message);
    process.exit(1);
  }
}

const args = process.argv.slice(2);
const main = args[0] ? args[0].toLowerCase() : 'help';

if (main === 'help') {
  showHelp();
  process.exit(0);
}

if (main === 'serve') {
  const sub = args[1] ? args[1].toLowerCase() : 'on';
  if (sub === 'on') {
    serveOn();
  } else if (sub === 'off') {
    serveOff();
  } else if (sub === 'status') {
    status();
  } else {
    console.error('Subcomando inválido para serve. Use on, off ou status.');
    showHelp();
    process.exit(1);
  }
  process.exit(0);
}

if (main === 'update') {
  updateProject();
  return;
}

if (main === 'status') {
  status();
  return;
}

if (main === 'phpserve') {
  phpServe();
  return;
}

console.error(`Comando inválido: ${main}`);
showHelp();
process.exit(1);
