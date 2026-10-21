/** A check or download must never restart a student's current lesson. */
export type UpdateDriver = {
 check:()=>Promise<{isAvailable:boolean;isRollBackToEmbedded:boolean}>;
 download:()=>Promise<unknown>;
 reload:()=>Promise<void>;
};
export class UpdateActions {
 private running=false;
 constructor(private driver:UpdateDriver,private onBusy:(busy:boolean)=>void=()=>{}){}
 private async run<T>(action:()=>Promise<T>):Promise<{value:T}|null>{
  if(this.running)return null;
  this.running=true;this.onBusy(true);
  try{return {value:await action()};}finally{this.running=false;this.onBusy(false);}
 }
 check(){return this.run(()=>this.driver.check());}
 download(){return this.run(()=>this.driver.download());}
 // Only the explicit confirmation handler calls this method.
 apply(){return this.run(()=>this.driver.reload());}
}
