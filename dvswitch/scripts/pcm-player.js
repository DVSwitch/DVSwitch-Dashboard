function PCMPlayer(options) {
    this.init(options);
}

function iOS() {
    return ["iPad Simulator", "iPhone Simulator", "iPod Simulator", "iPad", "iPhone", "iPod"].includes(navigator.platform) || 
           (navigator.userAgent.includes("Mac") && "ontouchend" in document);
}

function DVSwitchPlayer(port, button) {
    this.ws = null;
    
    // Auto-detect protocol for WebSocket connection
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    this.socketURL = protocol + "//" + document.location.hostname + ":" + port;
    
    this.sampleRate = iOS() ? 32000 : 8000;
    this.player = new PCMPlayer({
        encoding: "16bitInt",
        channels: 1,
        sampleRate: this.sampleRate,
        flushingTime: 2000
    });
    this.btn = button;
    this.btnDefault = button.style.backgroundColor;
    this.stop();
    
    window.addEventListener("unload", function() {
        this.destroy();
    });
}

function playAudioToggle(port, button) {
    if (window.dvsp === undefined) {
        window.dvsp = new DVSwitchPlayer(port, button);
    }
    if (dvsp.isPlaying()) {
        window.dvsp.stop();
    } else {
        window.dvsp.play();
    }
}

PCMPlayer.prototype.init = function(options) {
    this.option = Object.assign({}, {
        encoding: "16bitInt",
        channels: 1,
        sampleRate: 8000,
        flushingTime: 1000
    }, options);
    
    this.samples = new Float32Array();
    this.flush = this.flush.bind(this);
    this.interval = setInterval(this.flush, this.option.flushingTime);
    this.maxValue = this.getMaxValue();
    this.typedArray = this.getTypedArray();
    this.createContext();
};

PCMPlayer.prototype.getMaxValue = function() {
    const maxValues = {
        "8bitInt": 128,
        "16bitInt": 32768,
        "32bitInt": 2147483648,
        "32bitFloat": 1
    };
    return maxValues[this.option.encoding] ? maxValues[this.option.encoding] : maxValues["16bitInt"];
};

PCMPlayer.prototype.getTypedArray = function() {
    const typedArrays = {
        "8bitInt": Int8Array,
        "16bitInt": Int16Array,
        "32bitInt": Int32Array,
        "32bitFloat": Float32Array
    };
    return typedArrays[this.option.encoding] ? typedArrays[this.option.encoding] : typedArrays["16bitInt"];
};

PCMPlayer.prototype.createContext = function() {
    this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    this.gainNode = this.audioCtx.createGain();
    this.gainNode.gain.value = 1;
    this.gainNode.connect(this.audioCtx.destination);
    this.startTime = this.audioCtx.currentTime;
};

PCMPlayer.prototype.isTypedArray = function(data) {
    return data.byteLength && data.buffer && data.buffer.constructor === ArrayBuffer;
};

PCMPlayer.prototype.feed = function(data) {
    if (this.isTypedArray(data)) {
        data = this.getFormatedValue(data);
        const newSamples = new Float32Array(this.samples.length + data.length);
        newSamples.set(this.samples, 0);
        newSamples.set(data, this.samples.length);
        this.samples = newSamples;
    }
};

PCMPlayer.prototype.getFormatedValue = function(data) {
    const typedArray = new this.typedArray(data.buffer);
    const float32Array = new Float32Array(typedArray.length);
    for (let i = 0; i < typedArray.length; i++) {
        float32Array[i] = typedArray[i] / this.maxValue;
    }
    return float32Array;
};

PCMPlayer.prototype.volume = function(value) {
    this.gainNode.gain.value = value;
};

PCMPlayer.prototype.stop = function() {
    if (this.interval) {
        clearInterval(this.interval);
    }
    this.samples = null;
};

PCMPlayer.prototype.play = function() {
    this.samples = new Float32Array();
    this.interval = setInterval(this.flush, this.option.flushingTime);
};

PCMPlayer.prototype.destroy = function() {
    this.stop();
    this.audioCtx.close();
    this.audioCtx = null;
};

PCMPlayer.prototype.flush = function() {
    if (this.samples.length) {
        const source = this.audioCtx.createBufferSource();
        const length = this.samples.length / this.option.channels;
        const audioBuffer = this.audioCtx.createBuffer(this.option.channels, length, this.option.sampleRate);
        
        for (let channel = 0; channel < this.option.channels; channel++) {
            const channelData = audioBuffer.getChannelData(channel);
            let offset = channel;
            let fadeIn = 50;
            let fadeOut = 50;
            
            for (let i = 0; i < length; i++) {
                channelData[i] = this.samples[offset];
                if (i < 50) {
                    channelData[i] = channelData[i] * i / 50;
                }
                if (length - 51 <= i) {
                    channelData[i] = channelData[i] * fadeOut-- / 50;
                }
                offset += this.option.channels;
            }
        }
        
        if (this.startTime < this.audioCtx.currentTime) {
            this.startTime = this.audioCtx.currentTime;
        }
        
        console.log("start vs current " + this.startTime + " vs " + this.audioCtx.currentTime + " duration: " + audioBuffer.duration);
        
        source.buffer = audioBuffer;
        source.connect(this.gainNode);
        source.start(this.startTime);
        this.startTime += audioBuffer.duration;
        this.samples = new Float32Array();
    }
};

DVSwitchPlayer.prototype.destroy = function() {
    this.stop();
    this.player.destroy();
};

DVSwitchPlayer.prototype.play = function() {
    const self = this;
    this.btn.style.backgroundColor = "#008000";
    this.ws = new WebSocket(this.socketURL);
    this.ws.binaryType = "arraybuffer";
    this.ws.closing = false;
    
    this.ws.addEventListener("message", function(event) {
        const data = new Uint8Array(event.data);
        if (this.sampleRate === 8000) {
            self.player.feed(data);
        } else {
            const ratio = self.player.option.sampleRate / 8000;
            const newData = new Uint8Array(data.length * ratio);
            let offset = 0;
            for (let i = 0; i < data.length; i += 2) {
                for (let s = 0; s < ratio; s++) {
                    newData[offset++] = data[i];
                    newData[offset++] = data[i + 1];
                }
            }
            self.player.feed(newData);
        }
    });
    
    this.player.play();
    
    this.ws.onclose = function() {
        if (!this.closing) {
            self.ws = null;
            self.btn.style.backgroundColor = "#ff0000";
            setTimeout(function() {
                self.play(self.btn);
            }, 5000);
        }
    };
};

DVSwitchPlayer.prototype.stop = function() {
    this.player.stop();
    if (this.isPlaying()) {
        this.ws.closing = true;
        this.ws.close();
        this.ws = null;
    }
    this.btn.style.backgroundColor = this.btnDefault;
};

DVSwitchPlayer.prototype.isPlaying = function() {
    return this.ws !== null;
}; 