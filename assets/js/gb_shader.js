// Minimal Three.js CRT overlay. Progressive enhancement — if WebGL is missing, we ignore.
(function(){
  function initGBScreenEffect(){
    const host = document.getElementById('crt-overlay');
    if (!host || !window.THREE) return;
    const w = host.clientWidth, h = host.clientHeight;
    const renderer = new THREE.WebGLRenderer({alpha:true, antialias:false});
    renderer.setSize(w,h,false);
    host.appendChild(renderer.domElement);

    const scene = new THREE.Scene();
    const camera = new THREE.OrthographicCamera(-1,1,1,-1,0,1);

    const uniforms = {
      u_time:{value:0}, u_res:{value:new THREE.Vector2(w,h)},
      u_dark: {value: new THREE.Color(getComputedStyle(document.body).getPropertyValue('--lcd-dark'))},
      u_light:{value: new THREE.Color(getComputedStyle(document.body).getPropertyValue('--lcd-light'))}
    };
    const material = new THREE.ShaderMaterial({
      transparent:true,
      uniforms,
      vertexShader:`
        void main(){ gl_Position = vec4(position,1.0); }
      `,
      fragmentShader:`
        precision mediump float;
        uniform vec2 u_res; uniform float u_time;
        // simple moving scanlines and vignette
        void main(){
          vec2 uv = gl_FragCoord.xy / u_res;
          float scan = 0.06 * sin((uv.y + u_time*0.7)*400.0);
          float vign = smoothstep(0.0,0.7,length(uv-0.5));
          float alpha = clamp(0.15 + scan + vign*0.25, 0.0, 0.5);
          gl_FragColor = vec4(0.0,0.0,0.0, alpha);
        }
      `
    });

    const quad = new THREE.Mesh(new THREE.PlaneGeometry(2,2), material);
    scene.add(quad);

    function onResize(){
      const w2 = host.clientWidth, h2 = host.clientHeight;
      renderer.setSize(w2,h2,false);
      uniforms.u_res.value.set(w2,h2);
    }
    window.addEventListener('resize', onResize);

    function tick(t){
      material.uniforms.u_time.value = t*0.001;
      renderer.render(scene,camera);
      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }
  window.initGBScreenEffect = initGBScreenEffect;
})();