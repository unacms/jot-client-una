const util = require('util');
const fs = require('fs');
const exec = util.promisify(require('child_process').exec);

const sFiles = process.argv[2],
      sCommand = process.argv[3];

if (!sFiles)
    return;

async function runCommand(command) {
    console.log(command);
    const { stdout, stderr, error } = await exec(command);
    if(stderr){
        console.error('stderr:', stderr);
    }
    if(error){
        console.error('error:', error);
    }

    return stdout;
}

function* generator(files) {
    yield files.forEach((file) => {
        if (sCommand === 'remove')
            return fs.unlink('../' + file, function(err){
                if (err)
                    console.log(file + ' doesn\'t exists');
                else
                    console.log(file + ' was removed');
            });
        else
            return runCommand(`npm run compile${sCommand && ':' + sCommand} --FILE=${file}`);
    });
}

fs.readdir(sFiles, (err, files) => {
    const main = generator(files);
    while(!main.next().done);
});